<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class CreatePaymentRequestDto
{
    /** @param list<PaymentRequestItemDto> $items */
    public function __construct(
        public int $staffId,
        public int $branchId,
        public int $currencyId,
        public string $type,
        public string|null $notes,
        public array $items,
    ) {}
}
