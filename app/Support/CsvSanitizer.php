<?php

declare(strict_types=1);

namespace App\Support;

class CsvSanitizer
{
    /** @var array<int, string> */
    private const DANGEROUS_PREFIXES = ['=', '+', '-', '@', "\t", "\r"];

    /**
     * Neutralise CSV formula injection by prefixing risky string cells with a
     * single quote, so spreadsheet apps render them as text instead of formulas.
     *
     * @param array<int|string, mixed> $row
     *
     * @return array<int|string, mixed>
     */
    public static function sanitizeRow(array $row): array
    {
        return array_map(self::sanitizeValue(...), $row);
    }

    public static function sanitizeValue(mixed $value): mixed
    {
        if (! is_string($value) || $value === '') {
            return $value;
        }

        if (in_array($value[0], self::DANGEROUS_PREFIXES, true)) {
            return "'" . $value;
        }

        return $value;
    }
}
