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

    /** @return Collection<int, Currency> */
    public function allOrderedByShortName(): Collection
    {
        return Currency::orderBy('short_name')->get();
    }
}
