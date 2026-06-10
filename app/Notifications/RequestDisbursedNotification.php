<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestDisbursedNotification extends Notification implements ShouldQueue
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
        $rawMethod = $this->subject->getAttribute('disbursement_method');
        $method = $rawMethod instanceof \App\Enums\Tenant\PaymentMethod ? $rawMethod->label() : '—';
        $reference = $this->subject->getAttribute('disbursement_reference');
        $referenceStr = is_string($reference) ? $reference : null;

        $amount = $symbol . ' ' . number_format($totalAmount, 2);

        return (new MailMessage())
            ->subject(__('notifications.request_disbursed.subject', ['id' => $id]))
            ->greeting(__('notifications.greeting', ['name' => $recipient->first_name]))
            ->line(__('notifications.request_disbursed.disbursed'))
            ->line(__('notifications.request_disbursed.amount', ['amount' => $amount]))
            ->line(__('notifications.request_disbursed.method', ['method' => $method]))
            ->when($referenceStr !== null, fn($mail) => $mail->line(__('notifications.request_disbursed.reference', ['reference' => $referenceStr])))
            ->action(__('notifications.request_disbursed.action'), $url);
    }
}
