<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class PaymentRequestItemDto
{
    /** @param numeric-string $amount A two-place decimal string */
    public function __construct(
        public string $description,
        public string $amount,
        public int|null $costCodeId,
        public string|null $receiptNumber,
    ) {}
}
