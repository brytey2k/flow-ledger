<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class AccountCodeDto
{
    public function __construct(
        public string $code,
        public string $name,
        public int $departmentId,
    ) {}
}
