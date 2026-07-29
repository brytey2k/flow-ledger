<?php

declare(strict_types=1);
use App\Enums\Tenant\PaymentRequestStatus;

test('draft is cancelable', function () {
    expect(PaymentRequestStatus::Draft->isCancelable())->toBeTrue();
});
test('in workflow is cancelable', function () {
    expect(PaymentRequestStatus::InWorkflow->isCancelable())->toBeTrue();
});
test('approved is cancelable', function () {
    expect(PaymentRequestStatus::Approved->isCancelable())->toBeTrue();
});
test('sent back is cancelable', function () {
    expect(PaymentRequestStatus::SentBack->isCancelable())->toBeTrue();
});
test('disbursed is not cancelable', function () {
    expect(PaymentRequestStatus::Disbursed->isCancelable())->toBeFalse();
});
test('retired is not cancelable', function () {
    expect(PaymentRequestStatus::Retired->isCancelable())->toBeFalse();
});
test('cancelled is not cancelable', function () {
    expect(PaymentRequestStatus::Cancelled->isCancelable())->toBeFalse();
});
test('denied is not cancelable', function () {
    expect(PaymentRequestStatus::Denied->isCancelable())->toBeFalse();
});
test('draft label', function () {
    expect(PaymentRequestStatus::Draft->label())->toBe('Draft');
});
test('in workflow label', function () {
    expect(PaymentRequestStatus::InWorkflow->label())->toBe('In Workflow');
});
test('approved label', function () {
    expect(PaymentRequestStatus::Approved->label())->toBe('Approved');
});
test('disbursed label', function () {
    expect(PaymentRequestStatus::Disbursed->label())->toBe('Disbursed');
});
test('retired label', function () {
    expect(PaymentRequestStatus::Retired->label())->toBe('Retired');
});
test('cancelled label', function () {
    expect(PaymentRequestStatus::Cancelled->label())->toBe('Cancelled');
});
test('denied label', function () {
    expect(PaymentRequestStatus::Denied->label())->toBe('Denied');
});
test('sent back label', function () {
    expect(PaymentRequestStatus::SentBack->label())->toBe('Sent Back');
});
test('cases returns all eight statuses', function () {
    expect(PaymentRequestStatus::cases())->toHaveCount(8);
});
test('can be created from string value', function () {
    expect(PaymentRequestStatus::from('draft'))->toBe(PaymentRequestStatus::Draft);
    expect(PaymentRequestStatus::from('in_workflow'))->toBe(PaymentRequestStatus::InWorkflow);
    expect(PaymentRequestStatus::from('approved'))->toBe(PaymentRequestStatus::Approved);
    expect(PaymentRequestStatus::from('disbursed'))->toBe(PaymentRequestStatus::Disbursed);
    expect(PaymentRequestStatus::from('retired'))->toBe(PaymentRequestStatus::Retired);
    expect(PaymentRequestStatus::from('cancelled'))->toBe(PaymentRequestStatus::Cancelled);
    expect(PaymentRequestStatus::from('denied'))->toBe(PaymentRequestStatus::Denied);
    expect(PaymentRequestStatus::from('sent_back'))->toBe(PaymentRequestStatus::SentBack);
});
