<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class CommentDto
{
    public function __construct(
        public string $body,
    ) {}
}
