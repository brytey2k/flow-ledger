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

        return match ($this->recipientType) {
            'submitter' => (new MailMessage())
                ->subject("Action Required: Advance #{$id} is overdue for retirement")
                ->greeting("Hello {$recipient->first_name},")
                ->line("Your advance disbursement **#{$id}** is overdue. You are required to submit a retirement (expense report) to account for the funds.")
                ->line("**Advance Amount:** {$formattedAmount}")
                ->action('Submit Retirement', route('retirement-requests.create', $this->paymentRequest))
                ->line('Please submit your retirement with receipts and cost codes for all expenditures as soon as possible.'),
            'approver' => (new MailMessage())
                ->subject("Overdue Retirement: Advance #{$id} you approved has not been retired")
                ->greeting("Hello {$recipient->first_name},")
                ->line("Advance **#{$id}** that you approved is overdue for retirement. The staff member has not yet submitted an expense report.")
                ->line("**Advance Amount:** {$formattedAmount}")
                ->action('View Advance', $url)
                ->line('This is an automated reminder.'),
            default => (new MailMessage())
                ->subject("Overdue Retirement Alert: Advance #{$id} has not been retired")
                ->greeting("Hello {$recipient->first_name},")
                ->line("Advance **#{$id}** is overdue for retirement. No expense report has been submitted for this disbursement.")
                ->line("**Advance Amount:** {$formattedAmount}")
                ->action('View Advance', $url)
                ->line('This is an automated reminder.'),
        };
    }
}
