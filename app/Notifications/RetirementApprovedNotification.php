<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\RetirementRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RetirementApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly RetirementRequest $retirement) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('retirement-requests.show', $this->retirement);
        /** @var \App\Models\Tenant\User $recipient */
        $recipient = $notifiable;
        $pr = $this->retirement->paymentRequest;
        $rawRetirementId = $this->retirement->getKey();
        $retirementId = is_scalar($rawRetirementId) ? $rawRetirementId : 0;
        $rawPrId = $pr?->getKey();
        $prId = is_scalar($rawPrId) ? $rawPrId : 0;
        $currency = $pr?->getAttribute('currency');
        $currencySymbol = $currency instanceof \Illuminate\Database\Eloquent\Model ? $currency->getAttribute('symbol') : null;
        $symbol = is_string($currencySymbol) ? $currencySymbol : '';
        $rawExpended = $this->retirement->getAttribute('total_amount_expended');
        $totalExpended = is_numeric($rawExpended) ? (float) $rawExpended : 0.0;
        $rawDifferenceType = $this->retirement->getAttribute('difference_type');
        $differenceType = is_string($rawDifferenceType) ? $rawDifferenceType : '';
        $rawDifferenceAmount = $this->retirement->getAttribute('difference_amount');
        $differenceAmount = is_numeric($rawDifferenceAmount) ? (float) $rawDifferenceAmount : 0.0;

        return (new MailMessage())
            ->subject("Retirement #{$retirementId} Approved")
            ->greeting("Hello {$recipient->first_name},")
            ->line("Your retirement for Advance #{$prId} has been **fully approved**.")
            ->line('**Amount Expended:** ' . $symbol . ' ' . number_format($totalExpended, 2))
            ->when($differenceType !== 'nil' && $differenceType !== '', fn($mail) => $mail->line(
                '**Settlement:** ' . ucwords(str_replace('_', ' ', $differenceType)) .
                ' — ' . $symbol . ' ' . number_format($differenceAmount, 2),
            ))
            ->action('View Retirement', $url);
    }
}
