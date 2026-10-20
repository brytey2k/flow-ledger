<?php

declare(strict_types=1);

namespace App\DTOs\Landlord;

use Carbon\CarbonImmutable;

readonly class TenantDataToolFilters
{
    /** @param list<string> $statuses */
    public function __construct(
        public array $statuses = [],
        public CarbonImmutable|null $from = null,
        public CarbonImmutable|null $to = null,
        public bool $keepDrafts = false,
    ) {}

    /** @param array{statuses?: list<string>, from?: string|null, to?: string|null, keep_drafts?: bool} $data */
    public static function fromArray(array $data): self
    {
        $from = $data['from'] ?? null;
        $to = $data['to'] ?? null;

        return new self(
            $data['statuses'] ?? [],
            $from !== null && $from !== '' ? CarbonImmutable::parse($from)->startOfDay() : null,
            $to !== null && $to !== '' ? CarbonImmutable::parse($to)->endOfDay() : null,
            (bool) ($data['keep_drafts'] ?? false),
        );
    }

    /** @return array{statuses: list<string>, from: string|null, to: string|null, keep_drafts: bool} */
    public function toArray(): array
    {
        return [
            'statuses' => $this->statuses,
            'from' => $this->from?->toDateString(),
            'to' => $this->to?->toDateString(),
            'keep_drafts' => $this->keepDrafts,
        ];
    }
}
