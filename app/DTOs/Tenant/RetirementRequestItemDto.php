<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class RetirementRequestItemDto
{
    public function __construct(
        public string $description,
        public float $amount,
        public int $accountCodeId,
        public string|null $receiptNumber,
    ) {}
}
