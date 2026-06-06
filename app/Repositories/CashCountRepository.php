<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
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
}
