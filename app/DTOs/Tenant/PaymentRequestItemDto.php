<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class PaymentRequestItemDto
{
    public function __construct(
        public string $description,
        public float $amount,
        public int|null $accountCodeId,
        public string|null $receiptNumber,
    ) {}
}
