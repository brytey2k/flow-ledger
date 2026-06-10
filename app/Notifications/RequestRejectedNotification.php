<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Model $subject,
        public readonly string $comment,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('payment-requests.show', $this->subject);
        $rawId = $this->subject->getKey();
        $id = is_scalar($rawId) ? $rawId : 0;
        /** @var \App\Models\Tenant\User $recipient */
        $recipient = $notifiable;
        $currency = $this->subject->getAttribute('currency');
        $currencySymbol = $currency instanceof Model ? $currency->getAttribute('symbol') : null;
        $symbol = is_string($currencySymbol) ? $currencySymbol : '';
        $rawAmount = $this->subject->getAttribute('total_amount');
        $totalAmount = is_numeric($rawAmount) ? (float) $rawAmount : 0.0;

        $amount = $symbol . ' ' . number_format($totalAmount, 2);

        return (new MailMessage())
            ->subject(__('notifications.request_rejected.subject', ['id' => $id]))
            ->greeting(__('notifications.greeting', ['name' => $recipient->first_name]))
            ->line(__('notifications.request_rejected.rejected'))
            ->line(__('notifications.request_rejected.reason', ['reason' => $this->comment]))
            ->line(__('notifications.request_rejected.amount', ['amount' => $amount]))
            ->action(__('notifications.request_rejected.action'), $url)
            ->line(__('notifications.request_rejected.contact'));
    }
}
