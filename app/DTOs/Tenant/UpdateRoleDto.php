<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class UpdateRoleDto
{
    public function __construct(
        public string $name,
    ) {}
}
