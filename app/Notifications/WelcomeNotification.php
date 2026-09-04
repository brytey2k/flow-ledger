<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Notifications\Concerns\CapturesTenantDomain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeNotification extends Notification implements ShouldQueue
{
    use CapturesTenantDomain;
    use Queueable;

    public function __construct(public readonly string $token)
    {
        $this->domain = tenant_current_domain();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var \App\Models\Tenant\User $recipient */
        $recipient = $notifiable;

        $url = tenant_route_url($this->domain, 'password.reset', ['token' => $this->token]) . '?' . http_build_query([
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
