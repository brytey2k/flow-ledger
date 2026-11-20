<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\CashBalanceThreshold;
use App\Support\MailText;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
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
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $branch = $this->threshold->branch;
        /** @var string $branchName */
        $branchName = $branch->getAttribute('name');
        $rawThreshold = $this->threshold->getAttribute('threshold_amount');
        $thresholdAmount = is_numeric($rawThreshold) ? (float) $rawThreshold : 0.0;
        $currency = $branch->currency;
        /** @var string $currencySymbol */
        $currencySymbol = $currency->getAttribute('symbol') ?? '';

        $formattedBalance = $currencySymbol . ' ' . number_format($this->currentBalance, 2);
        $formattedThreshold = $currencySymbol . ' ' . number_format($thresholdAmount, 2);

        return (new MailMessage())
            ->subject(__('notifications.low_cash_balance.subject', ['branch' => $branchName]))
            ->greeting(__('notifications.low_cash_balance.greeting'))
            ->line(__('notifications.low_cash_balance.balance_fallen', ['branch' => MailText::plain($branchName)]))
            ->line(__('notifications.low_cash_balance.current_balance', ['amount' => $formattedBalance]))
            ->line(__('notifications.low_cash_balance.threshold', ['amount' => $formattedThreshold]))
            ->line(__('notifications.low_cash_balance.take_action'))
            ->line(__('notifications.low_cash_balance.automated'));
    }
}
