<?php

declare(strict_types=1);
use App\Enums\Tenant\PaymentMethod;

test('cash label', function () {
    expect(PaymentMethod::Cash->label())->toBe('Cash');
});
test('bank transfer label', function () {
    expect(PaymentMethod::BankTransfer->label())->toBe('Bank Transfer');
});
test('mobile money label', function () {
    expect(PaymentMethod::MobileMoney->label())->toBe('Mobile Money');
});
test('cheque label', function () {
    expect(PaymentMethod::Cheque->label())->toBe('Cheque');
});
