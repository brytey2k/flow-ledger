<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use Illuminate\Support\Collection;

class CurrencyDenominationRepository
{
    /**
     * @param Currency $currency
     * @param array{q?: string, type?: string} $filters
     *
     * @return Collection<int, CurrencyDenomination>
     */
    public function allForCurrency(Currency $currency, array $filters = []): Collection
    {
        $search = $filters['q'] ?? null;
        $type = $filters['type'] ?? null;

        return CurrencyDenomination::where('currency_id', $currency->id)
            ->when($search, fn($query) => $query->where('label', 'ilike', "%{$search}%"))
            ->when($type, fn($query) => $query->where('type', $type))
            ->orderBy('sort_order')
            ->orderBy('value')
            ->orderBy('id')
            ->get();
    }

    public function find(int $id): CurrencyDenomination|null
    {
        return CurrencyDenomination::find($id);
    }
}
