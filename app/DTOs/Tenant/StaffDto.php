<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class StaffDto
{
    public function __construct(
        public string $firstName,
        public string $lastName,
        public string|null $email,
        public string|null $phone,
        public int $departmentId,
        public int $positionId,
        public int|null $userId,
        public int|null $branchId,
    ) {}
}
