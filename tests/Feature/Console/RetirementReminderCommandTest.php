<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PaymentRequestStatus;
use App\Enums\Tenant\SettingKey;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Notifications\RetirementOverdueNotification;
use App\Repositories\SettingsRepository;
use App\Services\RetirementReminderService;
use Illuminate\Support\Facades\Notification;

function configureReminderSettings(array $overrides = []): void
{
    $settings = array_merge([
        'grace_period_days' => 7,
        'frequency_days' => 7,
        'notify_submitter' => false,
        'notify_approvers' => false,
        'notify_role_ids' => [test()->role->id],
    ], $overrides);

    app(SettingsRepository::class)->set(SettingKey::RetirementReminders, $settings);
}
function makeDisbursedAdvance(int $daysAgo): PaymentRequest
{
    return PaymentRequest::factory()->advance()->create([
        'branch_id' => test()->branch->id,
        'status' => PaymentRequestStatus::Disbursed->value,
        'disbursed_at' => now()->subDays($daysAgo)->startOfDay(),
    ]);
}
function sendReminders(): void
{
    app(RetirementReminderService::class)->sendReminders();
}
test('no notification sent before grace period', function () {
    Notification::fake();
    configureReminderSettings(['grace_period_days' => 7]);

    makeDisbursedAdvance(5);

    // only 5 days old, grace is 7
    sendReminders();

    Notification::assertNothingSent();
});
test('notification sent on first reminder day', function () {
    Notification::fake();
    configureReminderSettings(['grace_period_days' => 7, 'frequency_days' => 7]);

    $paymentRequest = makeDisbursedAdvance(7);

    sendReminders();

    Notification::assertSentTo($this->user, RetirementOverdueNotification::class);
    $this->assertDatabaseHas('retirement_reminder_logs', [
        'payment_request_id' => $paymentRequest->id,
        'user_id' => $this->user->id,
    ], 'tenant');
});
test('notification sent on second reminder day', function () {
    Notification::fake();
    configureReminderSettings(['grace_period_days' => 7, 'frequency_days' => 7]);

    makeDisbursedAdvance(14);

    // 7 grace + 7 frequency
    sendReminders();

    Notification::assertSentTo($this->user, RetirementOverdueNotification::class);
});
test('no notification sent between reminder intervals', function () {
    Notification::fake();
    configureReminderSettings(['grace_period_days' => 7, 'frequency_days' => 7]);

    makeDisbursedAdvance(10);

    // 10 days: 3 past grace, not a multiple of 7
    sendReminders();

    Notification::assertNothingSent();
});
test('no notification when retirement is submitted', function () {
    Notification::fake();
    configureReminderSettings(['grace_period_days' => 7]);

    $paymentRequest = makeDisbursedAdvance(10);
    RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'in_workflow',
    ]);

    sendReminders();

    Notification::assertNothingSent();
});
test('no notification when retirement is approved', function () {
    Notification::fake();
    configureReminderSettings(['grace_period_days' => 7]);

    $paymentRequest = makeDisbursedAdvance(10);
    RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'approved',
    ]);

    sendReminders();

    Notification::assertNothingSent();
});
test('notification sent when only draft retirement exists', function () {
    Notification::fake();
    configureReminderSettings(['grace_period_days' => 7]);

    $paymentRequest = makeDisbursedAdvance(7);
    RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'draft',
    ]);

    sendReminders();

    Notification::assertSentTo($this->user, RetirementOverdueNotification::class);
});
test('running command twice on same day does not duplicate notifications', function () {
    Notification::fake();
    configureReminderSettings(['grace_period_days' => 7]);

    makeDisbursedAdvance(7);

    sendReminders();
    sendReminders();

    Notification::assertSentToTimes($this->user, RetirementOverdueNotification::class, 1);
});
test('user in multiple recipient categories receives only one notification', function () {
    Notification::fake();

    $extraRole = App\Models\Role::create(['name' => 'extra_role_' . uniqid(), 'guard_name' => 'web']);
    $this->user->assignRole($extraRole);

    configureReminderSettings([
        'grace_period_days' => 7,
        'notify_role_ids' => [$this->role->id, $extraRole->id],
    ]);

    makeDisbursedAdvance(7);

    sendReminders();

    Notification::assertSentToTimes($this->user, RetirementOverdueNotification::class, 1);
});
test('expense requests are not reminded', function () {
    Notification::fake();
    configureReminderSettings(['grace_period_days' => 7]);

    PaymentRequest::factory()->expense()->create([
        'branch_id' => $this->branch->id,
        'status' => PaymentRequestStatus::Disbursed->value,
        'disbursed_at' => now()->subDays(10)->startOfDay(),
    ]);

    sendReminders();

    Notification::assertNothingSent();
});
test('default settings are applied when none stored', function () {
    // No settings stored — service should use defaults without throwing.
    sendReminders();
    expect(true)->toBeTrue();
});
test('notification uses role recipient type for role members', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'branch_id' => $this->branch->id,
    ]);

    $notification = new RetirementOverdueNotification($paymentRequest, 'role');
    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('Overdue Retirement Alert', $mail->subject);
});
test('notification uses submitter recipient type', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'branch_id' => $this->branch->id,
    ]);

    $notification = new RetirementOverdueNotification($paymentRequest, 'submitter');
    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('Action Required', $mail->subject);
    $this->assertStringContainsString('overdue for retirement', $mail->subject);
});
test('notification uses approver recipient type', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'branch_id' => $this->branch->id,
    ]);

    $notification = new RetirementOverdueNotification($paymentRequest, 'approver');
    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('you approved', $mail->subject);
});
test('artisan command exits successfully', function () {
    $this->artisan('retirement:send-reminders')
        ->assertSuccessful();

    // Re-initialize tenancy so tearDown can roll back tenant transaction
    tenancy()->initialize($this->tenant);
});
test('artisan command outputs tenant id in progress line', function () {
    $this->artisan('retirement:send-reminders')
        ->expectsOutputToContain($this->tenant->id)
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);
});
test('artisan command outputs sent count line', function () {
    $this->artisan('retirement:send-reminders')
        ->expectsOutputToContain('reminder')
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);
});
