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

        return (new MailMessage())
            ->subject("Action Required: {$stageName} — Request #{$subjectId}")
            ->greeting("Hello {$recipient->first_name},")
            ->line("A request is waiting for your approval at the **{$stageName}** stage.")
            ->line("**Request:** #{$subjectId} — " . $type)
            ->line('**Amount:** ' . $symbol . ' ' . number_format($totalAmount, 2))
            ->action('Review Request', $url)
            ->line('Please log in to approve, send back, or reject this request.');
    }
}
