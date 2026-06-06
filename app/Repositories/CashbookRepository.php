<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CashbookRepository
{
    public function findByBranchId(int $branchId): Cashbook|null
    {
        return Cashbook::where('branch_id', $branchId)->first();
    }

    /**
     * @param Cashbook $cashbook
     * @param array{type?:string,date_from?:string,date_to?:string,description?:string,amount_min?:string,amount_max?:string} $filters
     * @param int $perPage
     *
     * @return LengthAwarePaginator<int, CashbookEntry>
     */
    public function paginatedEntriesForCashbook(Cashbook $cashbook, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return $this->buildEntriesQuery($cashbook, $filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param Cashbook $cashbook
     * @param array{type?:string,date_from?:string,date_to?:string,description?:string,amount_min?:string,amount_max?:string} $filters
     *
     * @return Collection<int, CashbookEntry>
     */
    public function entriesForCashbook(Cashbook $cashbook, array $filters = []): Collection
    {
        return $this->buildEntriesQuery($cashbook, $filters)->get();
    }

    /**
     * @param Cashbook $cashbook
     * @param array{type?:string,date_from?:string,date_to?:string,description?:string,amount_min?:string,amount_max?:string} $filters
     */
    private function buildEntriesQuery(Cashbook $cashbook, array $filters = []): Builder
    {
        return CashbookEntry::query()
            ->where('cashbook_id', $cashbook->id)
            ->when($filters['type'] ?? null, fn(Builder $q, string $v) => $q->where('type', $v))
            ->when($filters['date_from'] ?? null, fn(Builder $q, string $v) => $q->whereDate('entry_date', '>=', $v))
            ->when($filters['date_to'] ?? null, fn(Builder $q, string $v) => $q->whereDate('entry_date', '<=', $v))
            ->when(
                $filters['description'] ?? null,
                fn(Builder $q, string $v) => $q->where(
                    fn(Builder $q) => $q->where('description', 'like', "%{$v}%")->orWhere('notes', 'like', "%{$v}%"),
                ),
            )
            ->when($filters['amount_min'] ?? null, fn(Builder $q, string $v) => $q->where('amount', '>=', $v))
            ->when($filters['amount_max'] ?? null, fn(Builder $q, string $v) => $q->where('amount', '<=', $v))
            ->with('sourceable')
            ->orderByDesc('entry_date')
            ->orderByDesc('id');
    }
}
