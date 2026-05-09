<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class SyncPermissionsDto
{
    /** @param array<int, int> $permissionIds */
    public function __construct(
        public array $permissionIds,
    ) {}
}
