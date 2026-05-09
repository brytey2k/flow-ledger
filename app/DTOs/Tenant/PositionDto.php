<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class PositionDto
{
    public function __construct(
        public string $name,
    ) {}
}
