<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RequestSentBackNotification extends Notification implements ShouldQueue
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

        return (new MailMessage())
            ->subject(__('notifications.request_sent_back.subject', ['id' => $id]))
            ->greeting(__('notifications.greeting', ['name' => $recipient->first_name]))
            ->line(__('notifications.request_sent_back.sent_back'))
            ->line(__('notifications.request_sent_back.feedback', ['feedback' => $this->comment]))
            ->action(__('notifications.request_sent_back.action'), $url)
            ->line(__('notifications.request_sent_back.resubmit'));
    }
}
