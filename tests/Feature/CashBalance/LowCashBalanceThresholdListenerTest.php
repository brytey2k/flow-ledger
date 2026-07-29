<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Events\CashbookBalanceChanged;
use App\Listeners\CheckCashBalanceThreshold;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceNotificationLog;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\User;
use App\Notifications\LowCashBalanceNotification;
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

    app(CheckCashBalanceThreshold::class)->handle(
        new CashbookBalanceChanged($cashbook, 1500.00, 900.00),
    );

    Notification::assertSentTo($recipient, LowCashBalanceNotification::class);
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
        new CashbookBalanceChanged($cashbook, 1500.00, 900.00),
    );

    Notification::assertNotSentTo($recipient, LowCashBalanceNotification::class);
    expect($threshold->notificationLogs()->count())->toBe(1);
});
