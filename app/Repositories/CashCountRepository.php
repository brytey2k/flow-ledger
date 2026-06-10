<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Pagination\LengthAwarePaginator;

class CashCountRepository
{
    /** @return LengthAwarePaginator<int, CashCount> */
    public function paginatedForCashbook(Cashbook $cashbook, int $perPage = 20): LengthAwarePaginator
    {
        return CashCount::where('cashbook_id', $cashbook->id)
            ->with(['countedBy', 'items'])
            ->orderByDesc('counted_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findWithItems(int $id): CashCount|null
    {
        return CashCount::with(['countedBy', 'items.denomination'])
            ->find($id);
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     *
     * @return EloquentCollection<int, CashCount>
     */
    public function forReport(array $allowedBranchIds, string $dateFrom, string $dateTo, int|string|null $branchId): EloquentCollection
    {
        return CashCount::with(['cashbook.branch', 'cashbook.currency', 'countedBy'])
            ->whereHas('cashbook', fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->whereDate('counted_at', '>=', $dateFrom)
            ->whereDate('counted_at', '<=', $dateTo)
            ->when($branchId, fn($q, $id) => $q->whereHas('cashbook', fn($sub) => $sub->where('branch_id', $id)))
            ->orderByDesc('counted_at')
            ->get();
    }
}
