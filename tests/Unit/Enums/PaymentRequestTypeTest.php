<?php

declare(strict_types=1);
use App\Enums\Tenant\PaymentRequestType;

test('cases returns all three types', function () {
    expect(PaymentRequestType::cases())->toHaveCount(3);
});
test('advance value', function () {
    expect(PaymentRequestType::Advance->value)->toBe('advance');
});
test('expense value', function () {
    expect(PaymentRequestType::Expense->value)->toBe('expense');
});
test('retirement value', function () {
    expect(PaymentRequestType::Retirement->value)->toBe('retirement');
});
test('can create advance from string', function () {
    expect(PaymentRequestType::from('advance'))->toBe(PaymentRequestType::Advance);
});
test('can create expense from string', function () {
    expect(PaymentRequestType::from('expense'))->toBe(PaymentRequestType::Expense);
});
test('can create retirement from string', function () {
    expect(PaymentRequestType::from('retirement'))->toBe(PaymentRequestType::Retirement);
});
test('try from returns null for unknown value', function () {
    expect(PaymentRequestType::tryFrom('unknown'))->toBeNull();
});
