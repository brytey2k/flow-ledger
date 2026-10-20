<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\User;
use App\Services\LoginAttemptLimiter;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountLockedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var User $recipient */
        $recipient = $notifiable;

        return (new MailMessage())
            ->subject(__('notifications.account_locked.subject', ['app_name' => config()->string('app.name')]))
            ->greeting(__('notifications.greeting', ['name' => $recipient->first_name]))
            ->line(__('notifications.account_locked.line_1', ['minutes' => intdiv(LoginAttemptLimiter::LOCKOUT_SECONDS, 60)]))
            ->line(__('notifications.account_locked.line_2'));
    }
}
