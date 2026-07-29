<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Notifications\RetirementOverdueNotification;

test('notification uses mail channel', function () {
    $paymentRequest = makePaymentRequestForRetirementOverdueNotification();
    $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');

    expect($notification->via($this->user))->toBe(['mail']);
});
test('to mail for submitter contains retirement create url', function () {
    $paymentRequest = makePaymentRequestForRetirementOverdueNotification();
    $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString(
        route('retirement-requests.create', $paymentRequest),
        collect($mail->actionUrl)->first() ?? $mail->actionUrl,
    );
});
test('to mail for submitter has overdue subject', function () {
    $paymentRequest = makePaymentRequestForRetirementOverdueNotification();
    $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('overdue', strtolower($mail->subject));
});
test('to mail for approver contains payment request show url', function () {
    $paymentRequest = makePaymentRequestForRetirementOverdueNotification();
    $notification = new RetirementOverdueNotification($paymentRequest, 'approver');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString(
        route('payment-requests.show', $paymentRequest),
        $mail->actionUrl,
    );
});
test('to mail for approver has overdue subject', function () {
    $paymentRequest = makePaymentRequestForRetirementOverdueNotification();
    $notification = new RetirementOverdueNotification($paymentRequest, 'approver');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('overdue', strtolower($mail->subject));
});
test('to mail for default recipient type contains show url', function () {
    $paymentRequest = makePaymentRequestForRetirementOverdueNotification();
    $notification = new RetirementOverdueNotification($paymentRequest, 'manager');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString(
        route('payment-requests.show', $paymentRequest),
        $mail->actionUrl,
    );
});
test('to mail greeting uses recipient first name', function () {
    $paymentRequest = makePaymentRequestForRetirementOverdueNotification();
    $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');

    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString($this->user->first_name, $mail->greeting);
});
test('to mail includes formatted amount in intro lines', function () {
    $currency = Currency::factory()->create(['symbol' => '$']);
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'branch_id' => $this->branch->id,
        'currency_id' => $currency->id,
        'total_amount' => 1500.00,
        'status' => 'approved',
    ]);

    $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');
    $mail = $notification->toMail($this->user);

    $introText = implode(' ', $mail->introLines);
    $this->assertStringContainsString('1,500.00', $introText);
});
function makePaymentRequestForRetirementOverdueNotification(): PaymentRequest
{
    $currency = Currency::factory()->create(['symbol' => 'GH₵']);

    return PaymentRequest::factory()->advance()->create([
        'branch_id' => test()->branch->id,
        'currency_id' => $currency->id,
        'total_amount' => 500.00,
        'status' => 'approved',
    ]);
}
