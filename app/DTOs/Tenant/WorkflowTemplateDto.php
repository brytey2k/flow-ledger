<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class WorkflowTemplateDto
{
    public function __construct(
        public string $name,
        public string $type,
    ) {}
}
