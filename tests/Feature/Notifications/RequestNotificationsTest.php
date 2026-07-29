<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Notifications\RequestApprovedNotification;
use App\Notifications\RequestDisbursedNotification;
use App\Notifications\RequestRejectedNotification;
use App\Notifications\RequestSentBackNotification;
use App\Notifications\RetirementApprovedNotification;
use App\Notifications\RetirementRequiredNotification;

test('request approved notification is sent via mail', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestApprovedNotification($paymentRequest);

    expect($notification->via($this->user))->toBe(['mail']);
});
test('request approved notification subject contains request id', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestApprovedNotification($paymentRequest);

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
});
test('request approved notification subject indicates approval', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestApprovedNotification($paymentRequest);

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('Approved', $mail->subject);
});
test('request rejected notification is sent via mail', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestRejectedNotification($paymentRequest, 'Budget exceeded');

    expect($notification->via($this->user))->toBe(['mail']);
});
test('request rejected notification subject contains request id', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestRejectedNotification($paymentRequest, 'Budget exceeded');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
});
test('request rejected notification mail contains rejection comment', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $rejectionComment = 'Budget has been exceeded for this period';
    $notification = new RequestRejectedNotification($paymentRequest, $rejectionComment);

    $mail = $notification->toMail($this->user);

    $introLines = implode(' ', $mail->introLines);
    $this->assertStringContainsString($rejectionComment, $introLines);
});
test('request rejected notification subject indicates rejection', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestRejectedNotification($paymentRequest, 'Not approved');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('Rejected', $mail->subject);
});
test('request disbursed notification is sent via mail', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestDisbursedNotification($paymentRequest);

    expect($notification->via($this->user))->toBe(['mail']);
});
test('request disbursed notification subject contains request id', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestDisbursedNotification($paymentRequest);

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
});
test('request disbursed notification subject indicates disbursement', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestDisbursedNotification($paymentRequest);

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('Disbursed', $mail->subject);
});
test('request disbursed notification mail contains amount', function () {
    $paymentRequest = PaymentRequest::factory()->create(['total_amount' => 500.00]);
    $notification = new RequestDisbursedNotification($paymentRequest);

    $mail = $notification->toMail($this->user);

    $introLines = implode(' ', $mail->introLines);
    $this->assertStringContainsString('500', $introLines);
});
test('request sent back notification is sent via mail', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestSentBackNotification($paymentRequest, 'Please revise the amounts');

    expect($notification->via($this->user))->toBe(['mail']);
});
test('request sent back notification subject contains request id', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestSentBackNotification($paymentRequest, 'Please revise');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
});
test('request sent back notification subject indicates sent back', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $notification = new RequestSentBackNotification($paymentRequest, 'Fix this');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('Sent Back', $mail->subject);
});
test('request sent back notification mail contains comment', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $comment = 'Please attach your receipts before resubmitting';
    $notification = new RequestSentBackNotification($paymentRequest, $comment);

    $mail = $notification->toMail($this->user);

    $introLines = implode(' ', $mail->introLines);
    $this->assertStringContainsString($comment, $introLines);
});
test('retirement approved notification is sent via mail', function () {
    $retirement = RetirementRequest::factory()->create();
    $notification = new RetirementApprovedNotification($retirement);

    expect($notification->via($this->user))->toBe(['mail']);
});
test('retirement approved notification subject contains retirement id', function () {
    $retirement = RetirementRequest::factory()->create();
    $notification = new RetirementApprovedNotification($retirement);

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString((string) $retirement->id, $mail->subject);
});
test('retirement approved notification subject indicates approval', function () {
    $retirement = RetirementRequest::factory()->create();
    $notification = new RetirementApprovedNotification($retirement);

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('Approved', $mail->subject);
});
test('retirement approved notification shows settlement line when difference exists', function () {
    $retirement = RetirementRequest::factory()->create([
        'difference_type' => 'refund',
        'difference_amount' => 250.00,
    ]);
    $notification = new RetirementApprovedNotification($retirement);

    $mail = $notification->toMail($this->user);

    $introLines = implode(' ', $mail->introLines);
    $this->assertStringContainsString('Settlement', $introLines);
});
test('retirement approved notification no settlement line when difference is nil', function () {
    $retirement = RetirementRequest::factory()->create([
        'difference_type' => 'nil',
        'difference_amount' => 0,
    ]);
    $notification = new RetirementApprovedNotification($retirement);

    $mail = $notification->toMail($this->user);

    $introLines = implode(' ', $mail->introLines);
    $this->assertStringNotContainsString('Settlement', $introLines);
});
test('retirement required notification is sent via mail', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);
    $notification = new RetirementRequiredNotification($paymentRequest);

    expect($notification->via($this->user))->toBe(['mail']);
});
test('retirement required notification subject contains request id', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);
    $notification = new RetirementRequiredNotification($paymentRequest);

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
});
test('retirement required notification subject indicates action required', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);
    $notification = new RetirementRequiredNotification($paymentRequest);

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('Action Required', $mail->subject);
});
test('retirement required notification mail contains advance amount', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'total_amount' => 1200.00,
    ]);
    $notification = new RetirementRequiredNotification($paymentRequest);

    $mail = $notification->toMail($this->user);

    $introLines = implode(' ', $mail->introLines);
    $this->assertStringContainsString('1,200', $introLines);
});
