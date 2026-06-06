<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use Illuminate\Support\Collection;

class CurrencyDenominationRepository
{
    /** @return Collection<int, CurrencyDenomination> */
    public function allForCurrency(Currency $currency): Collection
    {
        return CurrencyDenomination::where('currency_id', $currency->id)
            ->orderBy('sort_order')
            ->orderBy('value')
            ->get();
    }

    public function find(int $id): CurrencyDenomination|null
    {
        return CurrencyDenomination::find($id);
    }
}
