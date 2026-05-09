<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class DisbursePaymentRequestDto
{
    public function __construct(
        public string $method,
        public string|null $reference,
    ) {}
}
