<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class LevelDto
{
    public function __construct(
        public string $name,
        public int $position,
    ) {}
}
