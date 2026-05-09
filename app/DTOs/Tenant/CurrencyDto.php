<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class CurrencyDto
{
    public function __construct(
        public string $name,
        public string $shortName,
        public string $symbol,
    ) {}
}
