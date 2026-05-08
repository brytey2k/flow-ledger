<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RetirementRequiredNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly Model $subject) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('retirement-requests.create', $this->subject);
        $rawId = $this->subject->getKey();
        $id = is_scalar($rawId) ? $rawId : 0;
        /** @var \App\Models\Tenant\User $recipient */
        $recipient = $notifiable;
        $currency = $this->subject->getAttribute('currency');
        $currencySymbol = $currency instanceof Model ? $currency->getAttribute('symbol') : null;
        $symbol = is_string($currencySymbol) ? $currencySymbol : '';
        $rawAmount = $this->subject->getAttribute('total_amount');
        $totalAmount = is_numeric($rawAmount) ? (float) $rawAmount : 0.0;

        return (new MailMessage())
            ->subject("Action Required: Retire Advance #{$id}")
            ->greeting("Hello {$recipient->first_name},")
            ->line('You have received an advance disbursement. Please submit your **retirement (expense report)** to account for funds spent.')
            ->line('**Advance Amount:** ' . $symbol . ' ' . number_format($totalAmount, 2))
            ->action('Submit Retirement', $url)
            ->line('Please submit your retirement as soon as possible with receipts and account codes for all expenditures.');
    }
}
