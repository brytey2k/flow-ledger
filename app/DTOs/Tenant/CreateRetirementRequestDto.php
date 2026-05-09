<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class CreateRetirementRequestDto
{
    /** @param list<RetirementRequestItemDto> $items */
    public function __construct(
        public string|null $notes,
        public array $items,
    ) {}
}
