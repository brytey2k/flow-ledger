<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Currency;
use App\Models\Tenant\User;
use App\Notifications\LowCashBalanceNotification;

test('low cash balance notification is sent by mail only', function () {
    $notification = makeNotification();

    // Tenant databases have no notifications table and nothing reads in-app notifications.
    expect($notification->via($this->user))->toBe(['mail']);
});
test('low cash balance notification mail mentions branch and amounts', function () {
    $notification = makeNotification();
    $mail = $notification->toMail($this->user);

    $this->assertStringContainsString('Low Cash Balance Alert', $mail->subject);
    $this->assertStringContainsString('Main Branch', implode(' ', $mail->introLines));
    $this->assertStringContainsString('750.00', implode(' ', $mail->introLines));
});
function makeNotification(): LowCashBalanceNotification
{
    $currency = Currency::factory()->create(['symbol' => '₵']);
    $branch = Branch::factory()->create([
        'name' => 'Main Branch',
        'currency_id' => $currency->id,
        'level_id' => test()->level->id,
    ]);
    User::factory()->create([
        'branch_id' => $branch->id,
        'operational_branch_id' => $branch->id,
    ]);

    $threshold = CashBalanceThreshold::factory()->create([
        'branch_id' => $branch->id,
        'threshold_amount' => 1000.00,
    ]);

    return new LowCashBalanceNotification($threshold, 750.00);
}
