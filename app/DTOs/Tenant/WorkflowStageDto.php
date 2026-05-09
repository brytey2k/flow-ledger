<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class WorkflowStageDto
{
    /** @param array<int, int> $roleIds */
    public function __construct(
        public string $name,
        public int $displayOrder,
        public float|null $skipBelowAmount,
        public int|null $parallelGroupId,
        public array $roleIds,
    ) {}
}
