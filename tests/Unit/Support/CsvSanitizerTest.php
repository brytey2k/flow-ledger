<?php

declare(strict_types=1);

namespace Tests\Unit\Support;

use App\Support\CsvSanitizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class CsvSanitizerTest extends TestCase
{
    // ── sanitizeValue ─────────────────────────────────────────────────────────

    /** @return array<string, array{string, string}> */
    public static function dangerousValueProvider(): array
    {
        return [
            'equals formula' => ['=HYPERLINK("http://evil.test","click")', '\'=HYPERLINK("http://evil.test","click")'],
            'plus formula' => ['+cmd|"/c calc"', '\'+cmd|"/c calc"'],
            'minus formula' => ['-2+3', '\'-2+3'],
            'at formula' => ['@SUM(1+1)', '\'@SUM(1+1)'],
            'leading tab' => ["\t=1+1", "'\t=1+1"],
            'leading carriage return' => ["\r=1+1", "'\r=1+1"],
        ];
    }

    #[DataProvider('dangerousValueProvider')]
    public function test_sanitize_value_prefixes_dangerous_leading_characters(string $input, string $expected): void
    {
        $this->assertSame($expected, CsvSanitizer::sanitizeValue($input));
    }

    public function test_sanitize_value_leaves_safe_string_untouched(): void
    {
        $this->assertSame('Office supplies', CsvSanitizer::sanitizeValue('Office supplies'));
    }

    public function test_sanitize_value_leaves_dangerous_character_untouched_when_not_leading(): void
    {
        $this->assertSame('Total = 100', CsvSanitizer::sanitizeValue('Total = 100'));
    }

    public function test_sanitize_value_leaves_empty_string_untouched(): void
    {
        $this->assertSame('', CsvSanitizer::sanitizeValue(''));
    }

    public function test_sanitize_value_leaves_non_string_values_untouched(): void
    {
        $this->assertSame(42, CsvSanitizer::sanitizeValue(42));
        $this->assertSame(3.14, CsvSanitizer::sanitizeValue(3.14));
        $this->assertNull(CsvSanitizer::sanitizeValue(null));
        $this->assertTrue(CsvSanitizer::sanitizeValue(true));
    }

    // ── sanitizeRow ───────────────────────────────────────────────────────────

    public function test_sanitize_row_sanitises_each_cell_independently(): void
    {
        $row = ['2026-07-27', '=cmd|"/c calc"', 'Reference', 100.5, null];

        $this->assertSame(
            ['2026-07-27', '\'=cmd|"/c calc"', 'Reference', 100.5, null],
            CsvSanitizer::sanitizeRow($row),
        );
    }
}
