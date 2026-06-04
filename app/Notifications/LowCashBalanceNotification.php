<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\DatabaseMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowCashBalanceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly CashBalanceThreshold $threshold,
        public readonly float $currentBalance,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $branch = $this->threshold->branch;
        $branchName = $branch instanceof Branch ? $branch->getAttribute('name') : 'Unknown Branch';
        $thresholdAmount = (float) $this->threshold->getAttribute('threshold_amount');
        $currency = $branch instanceof Branch ? $branch->currency : null;
        $currencySymbol = $currency instanceof \Illuminate\Database\Eloquent\Model
            ? $currency->getAttribute('symbol')
            : '';

        return (new MailMessage())
            ->subject("⚠️ Low Cash Balance Alert: {$branchName}")
            ->greeting('Hello,')
            ->line("The cash balance for **{$branchName}** has fallen below the configured threshold.")
            ->line("**Current Balance:** {$currencySymbol} " . number_format($this->currentBalance, 2))
            ->line("**Threshold:** {$currencySymbol} " . number_format($thresholdAmount, 2))
            ->line('Please take necessary action to replenish the cash balance.')
            ->line('This is an automated alert. Please do not reply to this email.');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        $branch = $this->threshold->branch;
        $branchName = $branch instanceof Branch ? $branch->getAttribute('name') : 'Unknown Branch';
        $thresholdAmount = (float) $this->threshold->getAttribute('threshold_amount');

        return new DatabaseMessage([
            'type' => 'low_cash_balance',
            'branch_id' => $this->threshold->getAttribute('branch_id'),
            'branch_name' => $branchName,
            'current_balance' => $this->currentBalance,
            'threshold_amount' => $thresholdAmount,
            'message' => "Cash balance for {$branchName} is below threshold",
        ]);
    }
}
