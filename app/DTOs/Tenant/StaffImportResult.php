<?php

declare(strict_types=1);

namespace App\DTOs\Tenant;

readonly class StaffImportResult
{
    /** @param array<int, string> $errors */
    public function __construct(
        public int $imported,
        public int $skipped,
        public array $errors,
    ) {}
}
