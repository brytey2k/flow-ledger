<?php

declare(strict_types=1);

use App\Support\Money;

test('numeric strings become two-place decimals', function () {
    expect(Money::of('12'))->toBe('12.00')
        ->and(Money::of('12.5'))->toBe('12.50')
        ->and(Money::of('-3.40'))->toBe('-3.40');
});

test('ints and floats become two-place decimals', function () {
    expect(Money::of(7))->toBe('7.00')
        ->and(Money::of(0.1 + 0.2))->toBe('0.30');
});

test('anything that is not numeric counts as zero', function () {
    expect(Money::of(null))->toBe('0.00')
        ->and(Money::of('abc'))->toBe('0.00');
});

test('the largest amounts a decimal 15,2 column holds stay exact', function () {
    expect(Money::of('9999999999999.99'))->toBe('9999999999999.99');
});

test('sums are exact where float addition drifts', function () {
    expect(Money::sum(['0.10', '0.20']))->toBe('0.30')
        ->and(Money::sum(['4999999999999.99', '4999999999999.99']))->toBe('9999999999999.98')
        ->and(Money::sum([]))->toBe('0.00');
});
