<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class WorkflowParallelGroupDto
{
    public function __construct(
        public string $name,
        public bool $requireAll,
    ) {}
}
