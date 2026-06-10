<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use App\Enums\Tenant\CurrencyDenominationType;

readonly class CurrencyDenominationDto
{
    public function __construct(
        public int $currencyId,
        public string $value,
        public string $label,
        public CurrencyDenominationType $type = CurrencyDenominationType::Note,
        public int $sortOrder = 0,
    ) {}
}
