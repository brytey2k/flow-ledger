<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use App\Enums\Tenant\PaymentMethod;

readonly class CreatePaymentRequestDto
{
    /** @param list<PaymentRequestItemDto> $items */
    public function __construct(
        public int $staffId,
        public int $branchId,
        public string $type,
        public PaymentMethod|null $plannedDisbursementMethod,
        public string|null $notes,
        public array $items,
    ) {}
}
