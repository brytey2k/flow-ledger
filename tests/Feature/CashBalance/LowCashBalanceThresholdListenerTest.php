<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\UserStatus;
use App\Events\CashbookBalanceChanged;
use App\Listeners\CheckCashBalanceThreshold;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceNotificationLog;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Notifications\LowCashBalanceNotification;
use App\Services\CashbookService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Notification;

test('listener sends notification when balance drops below threshold', function () {
    Notification::fake();

    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create([
        'currency_id' => $currency->id,
        'level_id' => $this->level->id,
    ]);
    $recipient = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $invitedRecipient = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
        'status' => UserStatus::Invited,
    ]);
    $threshold = CashBalanceThreshold::factory()->create([
        'branch_id' => $branch->id,
        'threshold_amount' => 1000.00,
        'notification_user_ids' => [$recipient->id, $invitedRecipient->id],
        'cooldown_minutes' => 60,
    ]);
    $cashbook = Cashbook::create([
        'branch_id' => $branch->id,
        'currency_id' => $currency->id,
        'balance' => 900.00,
    ]);

    app(CheckCashBalanceThreshold::class)->handle(
        new CashbookBalanceChanged($cashbook, '1500.00', '900.00'),
    );

    Notification::assertSentTo($recipient, LowCashBalanceNotification::class);
    Notification::assertNotSentTo($invitedRecipient, LowCashBalanceNotification::class);
    expect($threshold->notificationLogs()->count())->toBe(1);
});
test('listener respects cooldown when recent notification exists', function () {
    Notification::fake();

    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create([
        'currency_id' => $currency->id,
        'level_id' => $this->level->id,
    ]);
    $recipient = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $threshold = CashBalanceThreshold::factory()->create([
        'branch_id' => $branch->id,
        'threshold_amount' => 1000.00,
        'notification_user_ids' => [$recipient->id],
        'cooldown_minutes' => 60,
    ]);
    $cashbook = Cashbook::create([
        'branch_id' => $branch->id,
        'currency_id' => $currency->id,
        'balance' => 900.00,
    ]);

    CashBalanceNotificationLog::factory()->create([
        'cash_balance_threshold_id' => $threshold->id,
        'balance_amount' => 900.00,
        'notified_at' => now()->subMinutes(15),
    ]);

    app(CheckCashBalanceThreshold::class)->handle(
        new CashbookBalanceChanged($cashbook, '1500.00', '900.00'),
    );

    Notification::assertNotSentTo($recipient, LowCashBalanceNotification::class);
    expect($threshold->notificationLogs()->count())->toBe(1);
});
/**
 * @param float $balance
 * @param float $thresholdAmount
 *
 * @return array{cashbook: Cashbook, recipient: User, threshold: CashBalanceThreshold}
 */
function fundedBranchWithLowBalanceThreshold(float $balance = 1500.00, float $thresholdAmount = 1000.00): array
{
    $recipient = User::factory()->create([
        'branch_id' => test()->branch->id,
        'operational_branch_id' => test()->branch->id,
    ]);
    $threshold = CashBalanceThreshold::factory()->create([
        'branch_id' => test()->branch->id,
        'threshold_amount' => $thresholdAmount,
        'notification_user_ids' => [$recipient->id],
        'cooldown_minutes' => 60,
    ]);
    $cashbook = Cashbook::create([
        'branch_id' => test()->branch->id,
        'currency_id' => Currency::factory()->create()->id,
        'balance' => $balance,
    ]);

    return ['cashbook' => $cashbook, 'recipient' => $recipient, 'threshold' => $threshold];
}
test('disbursing below the threshold sends the alert and keeps the disbursement', function () {
    ['cashbook' => $cashbook, 'threshold' => $threshold] = fundedBranchWithLowBalanceThreshold();
    $request = PaymentRequest::factory()->advance()->create([
        'status' => 'approved',
        'total_amount' => 600.00,
        'branch_id' => $this->branch->id,
        'currency_id' => $cashbook->currency_id,
    ]);

    $this->actingAs($this->user)->post(route('disbursements.store', $request));

    expect($request->fresh()->status)->toBe('disbursed');
    expect((float) $cashbook->fresh()->balance)->toEqualWithDelta(900.00, 0.01);
    expect($threshold->notificationLogs()->count())->toBe(1);
});
test('a failing low balance alert cannot roll back a disbursement', function () {
    Exceptions::fake();
    Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Mail server down'));
    ['cashbook' => $cashbook] = fundedBranchWithLowBalanceThreshold();
    $request = PaymentRequest::factory()->advance()->create([
        'status' => 'approved',
        'total_amount' => 600.00,
        'branch_id' => $this->branch->id,
        'currency_id' => $cashbook->currency_id,
    ]);

    DB::transaction(fn() => app(CashbookService::class)->recordDisbursement($request, $this->user));

    expect((float) $cashbook->fresh()->balance)->toEqualWithDelta(900.00, 0.01);
    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'sourceable_type' => PaymentRequest::class,
        'sourceable_id' => $request->id,
    ]);
    Exceptions::assertReported(fn(RuntimeException $e): bool => $e->getMessage() === 'Mail server down');
});
test('a failing low balance alert cannot roll back a retirement settlement', function () {
    Exceptions::fake();
    Notification::shouldReceive('send')->once()->andThrow(new RuntimeException('Mail server down'));
    ['cashbook' => $cashbook] = fundedBranchWithLowBalanceThreshold();
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'branch_id' => $this->branch->id,
        'currency_id' => $cashbook->currency_id,
    ]);
    $retirement = RetirementRequest::factory()->approved()->create([
        'payment_request_id' => $advance->id,
        'difference_type' => 'pay_to_staff',
        'difference_amount' => 600.00,
    ]);

    DB::transaction(fn() => app(CashbookService::class)->recordRetirementSettlement($retirement, $this->user));

    expect((float) $cashbook->fresh()->balance)->toEqualWithDelta(900.00, 0.01);
    Exceptions::assertReported(fn(RuntimeException $e): bool => $e->getMessage() === 'Mail server down');
});
