<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Notifications\RetirementOverdueNotification;
use Tests\TenantAppTestCase;

class RetirementOverdueNotificationTest extends TenantAppTestCase
{
    // ── via ───────────────────────────────────────────────────────────────────

    public function test_notification_uses_mail_channel(): void
    {
        $paymentRequest = $this->makePaymentRequest();
        $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');

        $this->assertSame(['mail'], $notification->via($this->user));
    }

    // ── toMail - submitter ────────────────────────────────────────────────────

    public function test_to_mail_for_submitter_contains_retirement_create_url(): void
    {
        $paymentRequest = $this->makePaymentRequest();
        $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString(
            route('retirement-requests.create', $paymentRequest),
            collect($mail->actionUrl)->first() ?? $mail->actionUrl,
        );
    }

    public function test_to_mail_for_submitter_has_overdue_subject(): void
    {
        $paymentRequest = $this->makePaymentRequest();
        $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('overdue', strtolower($mail->subject));
    }

    // ── toMail - approver ─────────────────────────────────────────────────────

    public function test_to_mail_for_approver_contains_payment_request_show_url(): void
    {
        $paymentRequest = $this->makePaymentRequest();
        $notification = new RetirementOverdueNotification($paymentRequest, 'approver');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString(
            route('payment-requests.show', $paymentRequest),
            $mail->actionUrl,
        );
    }

    public function test_to_mail_for_approver_has_overdue_subject(): void
    {
        $paymentRequest = $this->makePaymentRequest();
        $notification = new RetirementOverdueNotification($paymentRequest, 'approver');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('overdue', strtolower($mail->subject));
    }

    // ── toMail - default (unknown recipient type) ─────────────────────────────

    public function test_to_mail_for_default_recipient_type_contains_show_url(): void
    {
        $paymentRequest = $this->makePaymentRequest();
        $notification = new RetirementOverdueNotification($paymentRequest, 'manager');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString(
            route('payment-requests.show', $paymentRequest),
            $mail->actionUrl,
        );
    }

    // ── greeting ──────────────────────────────────────────────────────────────

    public function test_to_mail_greeting_uses_recipient_first_name(): void
    {
        $paymentRequest = $this->makePaymentRequest();
        $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString($this->user->first_name, $mail->greeting);
    }

    // ── amount ────────────────────────────────────────────────────────────────

    public function test_to_mail_includes_formatted_amount_in_intro_lines(): void
    {
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
    }

    private function makePaymentRequest(): PaymentRequest
    {
        $currency = Currency::factory()->create(['symbol' => 'GH₵']);

        return PaymentRequest::factory()->advance()->create([
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'total_amount' => 500.00,
            'status' => 'approved',
        ]);
    }
}
