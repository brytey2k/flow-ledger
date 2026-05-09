<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class BranchDto
{
    public function __construct(
        public string $name,
        public string|null $code,
        public int $levelId,
        public int $currencyId,
        public int|null $parentId,
        public int $position,
    ) {}
}
