<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use Illuminate\Pagination\LengthAwarePaginator;

class CashbookRepository
{
    public function findByBranchId(int $branchId): Cashbook|null
    {
        return Cashbook::where('branch_id', $branchId)->first();
    }

    /**
     * @param Cashbook $cashbook
     * @param int $perPage
     *
     * @return LengthAwarePaginator<int, CashbookEntry>
     */
    public function paginatedEntriesForCashbook(Cashbook $cashbook, int $perPage = 20): LengthAwarePaginator
    {
        return $cashbook->entries()
            ->with('sourceable')
            ->orderByDesc('entry_date')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
