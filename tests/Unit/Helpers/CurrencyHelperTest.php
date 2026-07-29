<?php

declare(strict_types=1);
use App\Helpers\CurrencyHelper;

test('get symbol returns ghs symbol', function () {
    expect(CurrencyHelper::getSymbol('GHS'))->toBe('GH₵');
});
test('get symbol returns usd symbol', function () {
    expect(CurrencyHelper::getSymbol('USD'))->toBe('$');
});
test('get symbol returns eur symbol', function () {
    expect(CurrencyHelper::getSymbol('EUR'))->toBe('€');
});
test('get symbol returns code itself as fallback for unknown currency', function () {
    expect(CurrencyHelper::getSymbol('UNKNOWN'))->toBe('UNKNOWN');
});
test('get symbol is case insensitive', function () {
    expect(CurrencyHelper::getSymbol('usd'))->toBe('$');
});
test('format usd amount with two decimal places', function () {
    expect(CurrencyHelper::format(1234.50, 'USD'))->toBe('$ 1,234.50');
});
test('format ghs amount with two decimal places', function () {
    expect(CurrencyHelper::format(1234.50, 'GHS'))->toBe('GH₵ 1,234.50');
});
test('format usd amount with zero decimal places', function () {
    expect(CurrencyHelper::format(1000, 'USD', 0))->toBe('$ 1,000');
});
test('was converted returns true when currencies differ', function () {
    expect(CurrencyHelper::wasConverted('GHS', 'USD'))->toBeTrue();
});
test('was converted returns false when currencies are the same', function () {
    expect(CurrencyHelper::wasConverted('USD', 'USD'))->toBeFalse();
});
