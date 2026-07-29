<?php

declare(strict_types=1);
use App\Support\CsvSanitizer;

// ── sanitizeValue ─────────────────────────────────────────────────────────
/* @return array<string, array{string, string}> */
dataset('dangerousValueProvider', fn() => [
    'equals formula' => ['=HYPERLINK("http://evil.test","click")', '\'=HYPERLINK("http://evil.test","click")'],
    'plus formula' => ['+cmd|"/c calc"', '\'+cmd|"/c calc"'],
    'minus formula' => ['-2+3', '\'-2+3'],
    'at formula' => ['@SUM(1+1)', '\'@SUM(1+1)'],
    'leading tab' => ["\t=1+1", "'\t=1+1"],
    'leading carriage return' => ["\r=1+1", "'\r=1+1"],
]);
test('sanitize value prefixes dangerous leading characters', function (string $input, string $expected) {
    expect(CsvSanitizer::sanitizeValue($input))->toBe($expected);
})->with('dangerousValueProvider');
test('sanitize value leaves safe string untouched', function () {
    expect(CsvSanitizer::sanitizeValue('Office supplies'))->toBe('Office supplies');
});
test('sanitize value leaves dangerous character untouched when not leading', function () {
    expect(CsvSanitizer::sanitizeValue('Total = 100'))->toBe('Total = 100');
});
test('sanitize value leaves empty string untouched', function () {
    expect(CsvSanitizer::sanitizeValue(''))->toBe('');
});
test('sanitize value leaves non string values untouched', function () {
    expect(CsvSanitizer::sanitizeValue(42))->toBe(42);
    expect(CsvSanitizer::sanitizeValue(3.14))->toBe(3.14);
    expect(CsvSanitizer::sanitizeValue(null))->toBeNull();
    expect(CsvSanitizer::sanitizeValue(true))->toBeTrue();
});
test('sanitize row sanitises each cell independently', function () {
    $row = ['2026-07-27', '=cmd|"/c calc"', 'Reference', 100.5, null];

    expect(CsvSanitizer::sanitizeRow($row))->toBe(['2026-07-27', '\'=cmd|"/c calc"', 'Reference', 100.5, null]);
});
