<?php

declare(strict_types=1);

namespace App\Enums\Tenant;

enum CurrencyDenominationType: string
{
    case Note = 'note';
    case Coin = 'coin';

    public function label(): string
    {
        return match ($this) {
            self::Note => 'Note',
            self::Coin => 'Coin',
        };
    }
}
