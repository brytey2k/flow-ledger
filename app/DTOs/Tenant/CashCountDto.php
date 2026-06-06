<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class CashCountDto
{
    /**
     * @param int $cashbookId
     * @param string|null $notes
     * @param array<int, array{denomination_id: int, quantity: int}> $items
     */
    public function __construct(
        public int $cashbookId,
        public string|null $notes,
        public array $items,
    ) {}
}
