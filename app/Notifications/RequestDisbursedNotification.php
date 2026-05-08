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
        $method = is_string($rawMethod) ? $rawMethod : '—';
        $reference = $this->subject->getAttribute('disbursement_reference');
        $referenceStr = is_string($reference) ? $reference : null;

        return (new MailMessage())
            ->subject("Request #{$id} Has Been Disbursed")
            ->greeting("Hello {$recipient->first_name},")
            ->line('Your approved request has been **disbursed**.')
            ->line('**Amount:** ' . $symbol . ' ' . number_format($totalAmount, 2))
            ->line('**Method:** ' . $method)
            ->when($referenceStr !== null, fn($mail) => $mail->line('**Reference:** ' . $referenceStr))
            ->action('View Request', $url);
    }
}
