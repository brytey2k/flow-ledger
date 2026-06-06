<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CurrencyDenominationDto;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use App\Models\Tenant\User;

class CurrencyDenominationService
{
    public function store(CurrencyDenominationDto $dto, User $user): CurrencyDenomination
    {
        $denomination = CurrencyDenomination::create([
            'currency_id' => $dto->currencyId,
            'value' => $dto->value,
            'label' => $dto->label,
            'sort_order' => $dto->sortOrder,
        ]);

        $currency = Currency::findOrFail($dto->currencyId);

        activity()
            ->performedOn($currency)
            ->causedBy($user)
            ->event('currency.denomination_added')
            ->withProperties(['denomination_id' => $denomination->id, 'value' => $dto->value, 'label' => $dto->label])
            ->log('Denomination added to currency');

        return $denomination;
    }

    public function update(CurrencyDenomination $denomination, CurrencyDenominationDto $dto, User $user): void
    {
        $denomination->update([
            'value' => $dto->value,
            'label' => $dto->label,
            'sort_order' => $dto->sortOrder,
        ]);

        activity()
            ->performedOn($denomination->currency)
            ->causedBy($user)
            ->event('currency.denomination_updated')
            ->withProperties(['denomination_id' => $denomination->id, 'value' => $dto->value, 'label' => $dto->label])
            ->log('Denomination updated');
    }

    public function delete(CurrencyDenomination $denomination, User $user): void
    {
        if ($denomination->cashCountItems()->exists()) {
            throw new \LogicException('This denomination cannot be deleted because it has been used in a cash count.');
        }

        $currency = $denomination->currency;
        $denominationId = $denomination->id;
        $label = $denomination->label;

        $denomination->delete();

        activity()
            ->performedOn($currency)
            ->causedBy($user)
            ->event('currency.denomination_deleted')
            ->withProperties(['denomination_id' => $denominationId, 'label' => $label])
            ->log('Denomination deleted from currency');
    }
}
