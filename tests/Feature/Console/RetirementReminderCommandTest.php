<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\Tenant\PaymentRequestStatus;
use App\Enums\Tenant\SettingKey;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Notifications\RetirementOverdueNotification;
use App\Repositories\SettingsRepository;
use App\Services\RetirementReminderService;
use Illuminate\Support\Facades\Notification;
use Tests\TenantAppTestCase;

class RetirementReminderCommandTest extends TenantAppTestCase
{
    private function configureReminderSettings(array $overrides = []): void
    {
        $settings = array_merge([
            'grace_period_days' => 7,
            'frequency_days' => 7,
            'notify_submitter' => false,
            'notify_approvers' => false,
            'notify_role_ids' => [$this->role->id],
        ], $overrides);

        app(SettingsRepository::class)->set(SettingKey::RetirementReminders, $settings);
    }

    private function makeDisbursedAdvance(int $daysAgo): PaymentRequest
    {
        return PaymentRequest::factory()->advance()->create([
            'branch_id' => $this->branch->id,
            'status' => PaymentRequestStatus::Disbursed->value,
            'disbursed_at' => now()->subDays($daysAgo)->startOfDay(),
        ]);
    }

    private function sendReminders(): void
    {
        app(RetirementReminderService::class)->sendReminders();
    }

    // ── Grace period not yet elapsed ─────────────────────────────────────────

    public function test_no_notification_sent_before_grace_period(): void
    {
        Notification::fake();
        $this->configureReminderSettings(['grace_period_days' => 7]);

        $this->makeDisbursedAdvance(5); // only 5 days old, grace is 7

        $this->sendReminders();

        Notification::assertNothingSent();
    }

    // ── Exactly at grace period ───────────────────────────────────────────────

    public function test_notification_sent_on_first_reminder_day(): void
    {
        Notification::fake();
        $this->configureReminderSettings(['grace_period_days' => 7, 'frequency_days' => 7]);

        $paymentRequest = $this->makeDisbursedAdvance(7);

        $this->sendReminders();

        Notification::assertSentTo($this->user, RetirementOverdueNotification::class);
        $this->assertDatabaseHas('retirement_reminder_logs', [
            'payment_request_id' => $paymentRequest->id,
            'user_id' => $this->user->id,
        ], 'tenant');
    }

    // ── Subsequent reminder at grace + frequency ──────────────────────────────

    public function test_notification_sent_on_second_reminder_day(): void
    {
        Notification::fake();
        $this->configureReminderSettings(['grace_period_days' => 7, 'frequency_days' => 7]);

        $this->makeDisbursedAdvance(14); // 7 grace + 7 frequency

        $this->sendReminders();

        Notification::assertSentTo($this->user, RetirementOverdueNotification::class);
    }

    // ── Between reminder intervals ────────────────────────────────────────────

    public function test_no_notification_sent_between_reminder_intervals(): void
    {
        Notification::fake();
        $this->configureReminderSettings(['grace_period_days' => 7, 'frequency_days' => 7]);

        $this->makeDisbursedAdvance(10); // 10 days: 3 past grace, not a multiple of 7

        $this->sendReminders();

        Notification::assertNothingSent();
    }

    // ── Retirement submitted — stop reminders ─────────────────────────────────

    public function test_no_notification_when_retirement_is_submitted(): void
    {
        Notification::fake();
        $this->configureReminderSettings(['grace_period_days' => 7]);

        $paymentRequest = $this->makeDisbursedAdvance(10);
        RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'in_workflow',
        ]);

        $this->sendReminders();

        Notification::assertNothingSent();
    }

    public function test_no_notification_when_retirement_is_approved(): void
    {
        Notification::fake();
        $this->configureReminderSettings(['grace_period_days' => 7]);

        $paymentRequest = $this->makeDisbursedAdvance(10);
        RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'approved',
        ]);

        $this->sendReminders();

        Notification::assertNothingSent();
    }

    // ── Draft retirement does NOT stop reminders ──────────────────────────────

    public function test_notification_sent_when_only_draft_retirement_exists(): void
    {
        Notification::fake();
        $this->configureReminderSettings(['grace_period_days' => 7]);

        $paymentRequest = $this->makeDisbursedAdvance(7);
        RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'draft',
        ]);

        $this->sendReminders();

        Notification::assertSentTo($this->user, RetirementOverdueNotification::class);
    }

    // ── Idempotency ───────────────────────────────────────────────────────────

    public function test_running_command_twice_on_same_day_does_not_duplicate_notifications(): void
    {
        Notification::fake();
        $this->configureReminderSettings(['grace_period_days' => 7]);

        $this->makeDisbursedAdvance(7);

        $this->sendReminders();
        $this->sendReminders();

        Notification::assertSentToTimes($this->user, RetirementOverdueNotification::class, 1);
    }

    // ── Deduplication: user in multiple recipient categories ─────────────────

    public function test_user_in_multiple_recipient_categories_receives_only_one_notification(): void
    {
        Notification::fake();

        $extraRole = \App\Models\Role::create(['name' => 'extra_role_' . uniqid(), 'guard_name' => 'web']);
        $this->user->assignRole($extraRole);

        $this->configureReminderSettings([
            'grace_period_days' => 7,
            'notify_role_ids' => [$this->role->id, $extraRole->id],
        ]);

        $this->makeDisbursedAdvance(7);

        $this->sendReminders();

        Notification::assertSentToTimes($this->user, RetirementOverdueNotification::class, 1);
    }

    // ── Expense requests are ignored ──────────────────────────────────────────

    public function test_expense_requests_are_not_reminded(): void
    {
        Notification::fake();
        $this->configureReminderSettings(['grace_period_days' => 7]);

        PaymentRequest::factory()->expense()->create([
            'branch_id' => $this->branch->id,
            'status' => PaymentRequestStatus::Disbursed->value,
            'disbursed_at' => now()->subDays(10)->startOfDay(),
        ]);

        $this->sendReminders();

        Notification::assertNothingSent();
    }

    // ── Default settings used when none stored ────────────────────────────────

    public function test_default_settings_are_applied_when_none_stored(): void
    {
        // No settings stored — service should use defaults without throwing.
        $this->sendReminders();
        $this->assertTrue(true);
    }

    // ── Notification content by recipient type ────────────────────────────────

    public function test_notification_uses_role_recipient_type_for_role_members(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'branch_id' => $this->branch->id,
        ]);

        $notification = new RetirementOverdueNotification($paymentRequest, 'role');
        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('Overdue Retirement Alert', $mail->subject);
    }

    public function test_notification_uses_submitter_recipient_type(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'branch_id' => $this->branch->id,
        ]);

        $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');
        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('Action Required', $mail->subject);
        $this->assertStringContainsString('overdue for retirement', $mail->subject);
    }

    public function test_notification_uses_approver_recipient_type(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'branch_id' => $this->branch->id,
        ]);

        $notification = new RetirementOverdueNotification($paymentRequest, 'approver');
        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('you approved', $mail->subject);
    }
}
