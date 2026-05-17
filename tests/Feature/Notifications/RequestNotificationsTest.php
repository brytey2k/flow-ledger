<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Notifications\RequestApprovedNotification;
use App\Notifications\RequestDisbursedNotification;
use App\Notifications\RequestRejectedNotification;
use App\Notifications\RequestSentBackNotification;
use App\Notifications\RetirementApprovedNotification;
use App\Notifications\RetirementRequiredNotification;
use Tests\TenantAppTestCase;

class RequestNotificationsTest extends TenantAppTestCase
{
    // ── RequestApprovedNotification ───────────────────────────────────────────

    public function test_request_approved_notification_is_sent_via_mail(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestApprovedNotification($paymentRequest);

        $this->assertSame(['mail'], $notification->via($this->user));
    }

    public function test_request_approved_notification_subject_contains_request_id(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestApprovedNotification($paymentRequest);

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
    }

    public function test_request_approved_notification_subject_indicates_approval(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestApprovedNotification($paymentRequest);

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('Approved', $mail->subject);
    }

    // ── RequestRejectedNotification ───────────────────────────────────────────

    public function test_request_rejected_notification_is_sent_via_mail(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestRejectedNotification($paymentRequest, 'Budget exceeded');

        $this->assertSame(['mail'], $notification->via($this->user));
    }

    public function test_request_rejected_notification_subject_contains_request_id(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestRejectedNotification($paymentRequest, 'Budget exceeded');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
    }

    public function test_request_rejected_notification_mail_contains_rejection_comment(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $rejectionComment = 'Budget has been exceeded for this period';
        $notification = new RequestRejectedNotification($paymentRequest, $rejectionComment);

        $mail = $notification->toMail($this->user);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString($rejectionComment, $introLines);
    }

    public function test_request_rejected_notification_subject_indicates_rejection(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestRejectedNotification($paymentRequest, 'Not approved');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('Rejected', $mail->subject);
    }

    // ── RequestDisbursedNotification ──────────────────────────────────────────

    public function test_request_disbursed_notification_is_sent_via_mail(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestDisbursedNotification($paymentRequest);

        $this->assertSame(['mail'], $notification->via($this->user));
    }

    public function test_request_disbursed_notification_subject_contains_request_id(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestDisbursedNotification($paymentRequest);

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
    }

    public function test_request_disbursed_notification_subject_indicates_disbursement(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestDisbursedNotification($paymentRequest);

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('Disbursed', $mail->subject);
    }

    public function test_request_disbursed_notification_mail_contains_amount(): void
    {
        $paymentRequest = PaymentRequest::factory()->create(['total_amount' => 500.00]);
        $notification = new RequestDisbursedNotification($paymentRequest);

        $mail = $notification->toMail($this->user);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString('500', $introLines);
    }

    // ── RequestSentBackNotification ───────────────────────────────────────────

    public function test_request_sent_back_notification_is_sent_via_mail(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestSentBackNotification($paymentRequest, 'Please revise the amounts');

        $this->assertSame(['mail'], $notification->via($this->user));
    }

    public function test_request_sent_back_notification_subject_contains_request_id(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestSentBackNotification($paymentRequest, 'Please revise');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
    }

    public function test_request_sent_back_notification_subject_indicates_sent_back(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $notification = new RequestSentBackNotification($paymentRequest, 'Fix this');

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('Sent Back', $mail->subject);
    }

    public function test_request_sent_back_notification_mail_contains_comment(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $comment = 'Please attach your receipts before resubmitting';
        $notification = new RequestSentBackNotification($paymentRequest, $comment);

        $mail = $notification->toMail($this->user);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString($comment, $introLines);
    }

    // ── RetirementApprovedNotification ────────────────────────────────────────

    public function test_retirement_approved_notification_is_sent_via_mail(): void
    {
        $retirement = RetirementRequest::factory()->create();
        $notification = new RetirementApprovedNotification($retirement);

        $this->assertSame(['mail'], $notification->via($this->user));
    }

    public function test_retirement_approved_notification_subject_contains_retirement_id(): void
    {
        $retirement = RetirementRequest::factory()->create();
        $notification = new RetirementApprovedNotification($retirement);

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString((string) $retirement->id, $mail->subject);
    }

    public function test_retirement_approved_notification_subject_indicates_approval(): void
    {
        $retirement = RetirementRequest::factory()->create();
        $notification = new RetirementApprovedNotification($retirement);

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('Approved', $mail->subject);
    }

    public function test_retirement_approved_notification_shows_settlement_line_when_difference_exists(): void
    {
        $retirement = RetirementRequest::factory()->create([
            'difference_type' => 'refund',
            'difference_amount' => 250.00,
        ]);
        $notification = new RetirementApprovedNotification($retirement);

        $mail = $notification->toMail($this->user);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString('Settlement', $introLines);
    }

    public function test_retirement_approved_notification_no_settlement_line_when_difference_is_nil(): void
    {
        $retirement = RetirementRequest::factory()->create([
            'difference_type' => 'nil',
            'difference_amount' => 0,
        ]);
        $notification = new RetirementApprovedNotification($retirement);

        $mail = $notification->toMail($this->user);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringNotContainsString('Settlement', $introLines);
    }

    // ── RetirementRequiredNotification ────────────────────────────────────────

    public function test_retirement_required_notification_is_sent_via_mail(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);
        $notification = new RetirementRequiredNotification($paymentRequest);

        $this->assertSame(['mail'], $notification->via($this->user));
    }

    public function test_retirement_required_notification_subject_contains_request_id(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);
        $notification = new RetirementRequiredNotification($paymentRequest);

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString((string) $paymentRequest->id, $mail->subject);
    }

    public function test_retirement_required_notification_subject_indicates_action_required(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);
        $notification = new RetirementRequiredNotification($paymentRequest);

        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('Action Required', $mail->subject);
    }

    public function test_retirement_required_notification_mail_contains_advance_amount(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'total_amount' => 1200.00,
        ]);
        $notification = new RetirementRequiredNotification($paymentRequest);

        $mail = $notification->toMail($this->user);

        $introLines = implode(' ', $mail->introLines);
        $this->assertStringContainsString('1,200', $introLines);
    }
}
