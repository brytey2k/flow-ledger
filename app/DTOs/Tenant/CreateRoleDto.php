<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class CreateRoleDto
{
    public function __construct(
        public string $name,
        public string $guardName,
    ) {}
}
