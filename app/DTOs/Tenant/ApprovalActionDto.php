<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class ApprovalActionDto
{
    public function __construct(
        public string $action,
        public string|null $comment,
    ) {}
}
