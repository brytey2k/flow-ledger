<?php

declare(strict_types=1);

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $token) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var \App\Models\Tenant\User $recipient */
        $recipient = $notifiable;

        $url = route('password.reset', ['token' => $this->token]) . '?' . http_build_query([
            'email' => $recipient->email,
        ]);

        return (new MailMessage())
            ->subject(__('notifications.welcome.subject', ['app_name' => config()->string('app.name')]))
            ->greeting(__('notifications.greeting', ['name' => $recipient->first_name]))
            ->line(__('notifications.welcome.line_1'))
            ->action(__('notifications.welcome.action'), $url)
            ->line(__('notifications.welcome.line_2'));
    }
}
