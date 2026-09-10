<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Currency;
use Illuminate\Database\Eloquent\Collection;

class CurrencyRepository
{
    /** @return Collection<int, Currency> */
    public function allOrderedByName(): Collection
    {
        return Currency::orderBy('name')->get();
    }

    /**
     * @param array{q?: string} $filters
     *
     * @return Collection<int, Currency>
     */
    public function allOrderedByShortName(array $filters = []): Collection
    {
        $search = $filters['q'] ?? null;

        return Currency::query()
            ->when($search, fn($query) => $query->where(
                fn($query) => $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('short_name', 'ilike', "%{$search}%")
                    ->orWhere('symbol', 'ilike', "%{$search}%"),
            ))
            ->orderBy('short_name')
            ->orderBy('id')
            ->get();
    }
}
