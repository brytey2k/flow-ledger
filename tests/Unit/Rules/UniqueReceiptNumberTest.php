<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\PaymentRequestItem;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\RetirementRequestItem;
use App\Rules\Tenant\UniqueReceiptNumber;

function passesUniqueReceiptNumberRule(string $receiptNumber, UniqueReceiptNumber $rule): bool
{
    $failed = false;
    $rule->validate('receipt_number', $receiptNumber, function () use (&$failed): void {
        $failed = true;
    });

    return ! $failed;
}
function makeClosure(): Closure
{
    return function (): void {};
}
test('passes when receipt number is unique', function () {
    $rule = new UniqueReceiptNumber();

    expect(passesUniqueReceiptNumberRule('REC-UNIQUE-001', $rule))->toBeTrue();
});
test('fails when receipt number exists on non cancelled retirement', function () {
    $retirement = RetirementRequest::factory()->create(['status' => 'draft']);
    RetirementRequestItem::factory()->create([
        'retirement_request_id' => $retirement->id,
        'receipt_number' => 'REC-001',
    ]);

    $rule = new UniqueReceiptNumber();

    expect(passesUniqueReceiptNumberRule('REC-001', $rule))->toBeFalse();
});
test('passes when receipt number only exists on cancelled retirement', function () {
    $retirement = RetirementRequest::factory()->create(['status' => 'cancelled']);
    RetirementRequestItem::factory()->create([
        'retirement_request_id' => $retirement->id,
        'receipt_number' => 'REC-CANCELLED',
    ]);

    $rule = new UniqueReceiptNumber();

    expect(passesUniqueReceiptNumberRule('REC-CANCELLED', $rule))->toBeTrue();
});
test('passes when excluding own retirement request id', function () {
    $retirement = RetirementRequest::factory()->create(['status' => 'draft']);
    RetirementRequestItem::factory()->create([
        'retirement_request_id' => $retirement->id,
        'receipt_number' => 'REC-EDIT',
    ]);

    $rule = new UniqueReceiptNumber(excludeRetirementRequestId: $retirement->id);

    expect(passesUniqueReceiptNumberRule('REC-EDIT', $rule))->toBeTrue();
});
test('fails for other retirement even when own is excluded', function () {
    $own = RetirementRequest::factory()->create(['status' => 'draft']);
    RetirementRequestItem::factory()->create([
        'retirement_request_id' => $own->id,
        'receipt_number' => 'REC-SHARED',
    ]);

    $other = RetirementRequest::factory()->create(['status' => 'draft']);
    RetirementRequestItem::factory()->create([
        'retirement_request_id' => $other->id,
        'receipt_number' => 'REC-SHARED',
    ]);

    $rule = new UniqueReceiptNumber(excludeRetirementRequestId: $own->id);

    expect(passesUniqueReceiptNumberRule('REC-SHARED', $rule))->toBeFalse();
});
test('fails when receipt number exists on active payment request', function () {
    $payment = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
    PaymentRequestItem::factory()->create([
        'payment_request_id' => $payment->id,
        'receipt_number' => 'REC-PAY-001',
    ]);

    $rule = new UniqueReceiptNumber();

    expect(passesUniqueReceiptNumberRule('REC-PAY-001', $rule))->toBeFalse();
});
test('passes when receipt number only exists on cancelled payment request', function () {
    $payment = PaymentRequest::factory()->advance()->create(['status' => 'cancelled']);
    PaymentRequestItem::factory()->create([
        'payment_request_id' => $payment->id,
        'receipt_number' => 'REC-PAY-CANCELLED',
    ]);

    $rule = new UniqueReceiptNumber();

    expect(passesUniqueReceiptNumberRule('REC-PAY-CANCELLED', $rule))->toBeTrue();
});
test('passes when receipt number only exists on denied payment request', function () {
    $payment = PaymentRequest::factory()->advance()->create(['status' => 'denied']);
    PaymentRequestItem::factory()->create([
        'payment_request_id' => $payment->id,
        'receipt_number' => 'REC-PAY-DENIED',
    ]);

    $rule = new UniqueReceiptNumber();

    expect(passesUniqueReceiptNumberRule('REC-PAY-DENIED', $rule))->toBeTrue();
});
test('passes when excluding own payment request id', function () {
    $payment = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
    PaymentRequestItem::factory()->create([
        'payment_request_id' => $payment->id,
        'receipt_number' => 'REC-PAY-EDIT',
    ]);

    $rule = new UniqueReceiptNumber(excludePaymentRequestId: $payment->id);

    expect(passesUniqueReceiptNumberRule('REC-PAY-EDIT', $rule))->toBeTrue();
});
test('fails when receipt number exists on retirement even if not on payment', function () {
    $retirement = RetirementRequest::factory()->create(['status' => 'in_workflow']);
    RetirementRequestItem::factory()->create([
        'retirement_request_id' => $retirement->id,
        'receipt_number' => 'REC-CROSS',
    ]);

    $rule = new UniqueReceiptNumber();

    expect(passesUniqueReceiptNumberRule('REC-CROSS', $rule))->toBeFalse();
});
test('fails when receipt number exists on payment even if not on retirement', function () {
    $payment = PaymentRequest::factory()->advance()->create(['status' => 'approved']);
    PaymentRequestItem::factory()->create([
        'payment_request_id' => $payment->id,
        'receipt_number' => 'REC-CROSS-PAY',
    ]);

    $rule = new UniqueReceiptNumber();

    expect(passesUniqueReceiptNumberRule('REC-CROSS-PAY', $rule))->toBeFalse();
});
