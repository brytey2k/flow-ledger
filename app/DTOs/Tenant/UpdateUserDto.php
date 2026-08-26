<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class UpdateUserDto
{
    /** @param array<int, int> $roles */
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string $email,
        public array $roles = [],
    ) {}
}
