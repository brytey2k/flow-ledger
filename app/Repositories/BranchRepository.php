<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Branch;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class BranchRepository
{
    /** @return Collection<int, Branch> */
    public function allWithRelations(): Collection
    {
        return Branch::with(['level', 'parent'])->orderBy('position')->get();
    }

    /** @return Collection<int, Branch> */
    public function allWithCashbook(): Collection
    {
        return Branch::with(['level', 'currency', 'cashbook.currency', 'cashBalanceThreshold'])
            ->orderBy('position')
            ->get();
    }

    /** @return Collection<int, Branch> */
    public function allOrderedByName(): Collection
    {
        return Branch::orderBy('name')->get();
    }

    /**
     * @param array<int, int> $branchIds
     *
     * @return SupportCollection<int, string>
     */
    public function allByIdsOrderedByName(array $branchIds): SupportCollection
    {
        /** @var SupportCollection<int, string> $result */
        $result = Branch::whereIn('id', $branchIds)
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->pluck('name', 'id');

        return $result;
    }

    /** @return Collection<int, Branch> */
    public function allExcept(int $excludeId): Collection
    {
        return Branch::where('id', '!=', $excludeId)->orderBy('name')->get();
    }

    public function findOrFail(int $id): Branch
    {
        /* @var Branch */
        return Branch::findOrFail($id);
    }

    /**
     * @param array<int, int> $branchIds
     *
     * @return SupportCollection<int, array{
     *     id: int,
     *     name: string,
     *     balance: float,
     *     threshold: float,
     *     currency_symbol: string,
     *     currency_code: string
     * }>
     */
    public function lowCashBranchAlerts(array $branchIds): SupportCollection
    {
        /** @var SupportCollection<int, object{
         *      id: int,
         *      name: string,
         *      balance: float|int|string,
         *      threshold: float|int|string,
         *      currency_symbol: string|null,
         *      currency_code: string|null
         * }>
         */
        $rows = DB::table('branches')
            ->join('cashbooks', 'cashbooks.branch_id', '=', 'branches.id')
            ->join('cash_balance_thresholds as thresholds', function (JoinClause $join): void {
                $join->on('thresholds.branch_id', '=', 'branches.id')
                    ->where('thresholds.is_active', '=', true);
            })
            ->leftJoin('currencies', 'currencies.id', '=', 'cashbooks.currency_id')
            ->whereIn('branches.id', $branchIds)
            ->whereNull('branches.deleted_at')
            ->whereRaw('cashbooks.balance < thresholds.threshold_amount')
            ->selectRaw('branches.id, branches.name, cashbooks.balance, thresholds.threshold_amount as threshold, currencies.symbol as currency_symbol, currencies.short_name as currency_code')
            ->orderByRaw('(thresholds.threshold_amount - cashbooks.balance) DESC')
            ->get();

        /** @var SupportCollection<int, array{id: int, name: string, balance: float, threshold: float, currency_symbol: string, currency_code: string}> $result */
        $result = $rows->map(static fn(object $row): array => [
            'id' => (int) $row->id,
            'name' => (string) $row->name,
            'balance' => (float) $row->balance,
            'threshold' => (float) $row->threshold,
            'currency_symbol' => (string) ($row->currency_symbol ?? ''),
            'currency_code' => (string) ($row->currency_code ?? ''),
        ])->values();

        return $result;
    }
}
