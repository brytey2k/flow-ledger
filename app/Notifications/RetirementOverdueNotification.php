<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\PaymentRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RetirementOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly PaymentRequest $paymentRequest,
        public readonly string $recipientType,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var \App\Models\Tenant\User $recipient */
        $recipient = $notifiable;
        $id = $this->paymentRequest->getKey();
        $currency = $this->paymentRequest->currency;
        $symbol = is_object($currency) ? (string) ($currency->getAttribute('symbol') ?? '') : '';
        $totalAmount = (float) ($this->paymentRequest->getAttribute('total_amount') ?? 0);
        $url = route('payment-requests.show', $this->paymentRequest);
        $formattedAmount = $symbol . ' ' . number_format($totalAmount, 2);

        $greeting = __('notifications.greeting', ['name' => $recipient->first_name]);
        $key = match ($this->recipientType) {
            'submitter', 'approver' => $this->recipientType,
            default => 'default',
        };

        return match ($this->recipientType) {
            'submitter' => (new MailMessage())
                ->subject(__("notifications.retirement_overdue.{$key}.subject", ['id' => $id]))
                ->greeting($greeting)
                ->line(__("notifications.retirement_overdue.{$key}.line1", ['id' => $id]))
                ->line(__("notifications.retirement_overdue.{$key}.amount", ['amount' => $formattedAmount]))
                ->action(__("notifications.retirement_overdue.{$key}.action"), route('retirement-requests.create', $this->paymentRequest))
                ->line(__("notifications.retirement_overdue.{$key}.reminder")),
            default => (new MailMessage())
                ->subject(__("notifications.retirement_overdue.{$key}.subject", ['id' => $id]))
                ->greeting($greeting)
                ->line(__("notifications.retirement_overdue.{$key}.line1", ['id' => $id]))
                ->line(__("notifications.retirement_overdue.{$key}.amount", ['amount' => $formattedAmount]))
                ->action(__("notifications.retirement_overdue.{$key}.action"), $url)
                ->line(__("notifications.retirement_overdue.{$key}.reminder")),
        };
    }
}
