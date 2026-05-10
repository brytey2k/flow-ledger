<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

use Carbon\Carbon;

readonly class CashbookEntryDto
{
    public function __construct(
        public string $amount,
        public Carbon $entryDate,
        public string|null $reference,
        public string|null $notes,
    ) {}
}
