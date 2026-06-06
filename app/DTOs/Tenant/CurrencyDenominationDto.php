<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class CurrencyDenominationDto
{
    public function __construct(
        public int $currencyId,
        public string $value,
        public string $label,
        public int $sortOrder,
    ) {}
}
