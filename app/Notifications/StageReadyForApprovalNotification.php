<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\WorkflowInstanceStage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StageReadyForApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly WorkflowInstanceStage $instanceStage) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $instance = $this->instanceStage->instance;
        /** @var \Illuminate\Database\Eloquent\Model|null $workflowable */
        $workflowable = $instance?->workflowable;
        /** @var \App\Models\Tenant\WorkflowStage|null $stage */
        $stage = $this->instanceStage->stage;
        $stageName = $stage !== null ? $stage->name : 'Stage';
        $url = route('approvals.show', $this->instanceStage);
        /** @var \App\Models\Tenant\User $recipient */
        $recipient = $notifiable;
        $rawId = $workflowable?->getKey();
        $subjectId = is_scalar($rawId) ? $rawId : 0;
        $rawType = $workflowable?->getAttribute('type');
        $type = ucfirst(is_string($rawType) ? $rawType : 'request');
        $currency = $workflowable?->getAttribute('currency');
        $currencySymbol = $currency instanceof \Illuminate\Database\Eloquent\Model ? $currency->getAttribute('symbol') : null;
        $symbol = is_string($currencySymbol) ? $currencySymbol : '';
        $rawAmount = $workflowable?->getAttribute('total_amount');
        $totalAmount = is_numeric($rawAmount) ? (float) $rawAmount : 0.0;

        $amount = $symbol . ' ' . number_format($totalAmount, 2);

        return (new MailMessage())
            ->subject(__('notifications.stage_ready.subject', ['stage' => $stageName, 'id' => $subjectId]))
            ->greeting(__('notifications.greeting', ['name' => $recipient->first_name]))
            ->line(__('notifications.stage_ready.waiting', ['stage' => $stageName]))
            ->line(__('notifications.stage_ready.request', ['id' => $subjectId, 'type' => $type]))
            ->line(__('notifications.stage_ready.amount', ['amount' => $amount]))
            ->action(__('notifications.stage_ready.action'), $url)
            ->line(__('notifications.stage_ready.login'));
    }
}
