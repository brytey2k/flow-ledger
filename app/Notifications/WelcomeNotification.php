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

    public function __construct(public readonly string $temporaryPassword) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var \App\Models\Tenant\User $recipient */
        $recipient = $notifiable;

        return (new MailMessage())
            ->subject(__('notifications.welcome.subject', ['app_name' => config()->string('app.name')]))
            ->greeting(__('notifications.greeting', ['name' => $recipient->first_name]))
            ->line(__('notifications.welcome.line_1'))
            ->line(__('notifications.welcome.password', ['password' => $this->temporaryPassword]))
            ->line(__('notifications.welcome.line_2'))
            ->action(__('notifications.welcome.action'), route('login'));
    }
}
