<?php

declare(strict_types=1);
use App\Enums\Tenant\CurrencyDenominationType;

test('cases returns two types', function () {
    expect(CurrencyDenominationType::cases())->toHaveCount(2);
});
test('note value', function () {
    expect(CurrencyDenominationType::Note->value)->toBe('note');
});
test('coin value', function () {
    expect(CurrencyDenominationType::Coin->value)->toBe('coin');
});
test('note label', function () {
    expect(CurrencyDenominationType::Note->label())->toBe('Note');
});
test('coin label', function () {
    expect(CurrencyDenominationType::Coin->label())->toBe('Coin');
});
test('can create note from string', function () {
    expect(CurrencyDenominationType::from('note'))->toBe(CurrencyDenominationType::Note);
});
test('can create coin from string', function () {
    expect(CurrencyDenominationType::from('coin'))->toBe(CurrencyDenominationType::Coin);
});
test('try from returns null for unknown value', function () {
    expect(CurrencyDenominationType::tryFrom('unknown'))->toBeNull();
});
