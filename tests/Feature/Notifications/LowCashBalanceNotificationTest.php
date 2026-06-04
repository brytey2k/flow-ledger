<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Currency;
use App\Models\Tenant\User;
use App\Notifications\LowCashBalanceNotification;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Tests\TenantAppTestCase;

class LowCashBalanceNotificationTest extends TenantAppTestCase
{
    public function test_low_cash_balance_notification_uses_mail_and_database_channels(): void
    {
        $notification = $this->makeNotification();

        $this->assertSame(['mail', 'database'], $notification->via($this->user));
    }

    public function test_low_cash_balance_notification_mail_mentions_branch_and_amounts(): void
    {
        $notification = $this->makeNotification();
        $mail = $notification->toMail($this->user);

        $this->assertStringContainsString('Low Cash Balance Alert', $mail->subject);
        $this->assertStringContainsString('Main Branch', implode(' ', $mail->introLines));
        $this->assertStringContainsString('750.00', implode(' ', $mail->introLines));
    }

    public function test_low_cash_balance_notification_database_payload_is_structured(): void
    {
        $notification = $this->makeNotification();
        $message = $notification->toDatabase($this->user);

        $this->assertInstanceOf(DatabaseMessage::class, $message);
        $this->assertSame('low_cash_balance', $message->data['type']);
        $this->assertSame('Main Branch', $message->data['branch_name']);
        $this->assertSame(750.0, $message->data['current_balance']);
        $this->assertSame(1000.0, $message->data['threshold_amount']);
    }

    private function makeNotification(): LowCashBalanceNotification
    {
        $currency = Currency::factory()->create(['symbol' => '₵']);
        $branch = Branch::factory()->create([
            'name' => 'Main Branch',
            'currency_id' => $currency->id,
            'level_id' => $this->level->id,
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
}
