<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowDocumentRequest;
use App\Notifications\Concerns\CapturesTenantDomain;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkflowDocumentsSubmittedNotification extends Notification implements ShouldQueue
{
    use CapturesTenantDomain;
    use Queueable;

    public function __construct(public readonly WorkflowDocumentRequest $documentRequest)
    {
        $this->domain = tenant_current_domain();
        $this->afterCommit();
    }

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $request = $this->documentRequest->instance?->workflowable;
        $requester = $this->documentRequest->requester;
        $openedBy = $this->documentRequest->openedBy;
        $stage = $this->documentRequest->openingStage?->stage;
        if ((! $request instanceof PaymentRequest && ! $request instanceof RetirementRequest) || $requester === null || $openedBy === null || $stage === null) {
            throw new \LogicException('The document request notification context is incomplete.');
        }
        $route = $request instanceof RetirementRequest ? 'retirement-requests.show' : 'payment-requests.show';
        $files = $this->documentRequest->attachments->pluck('original_name')->implode(', ');
        $requestId = $request->getKey();
        $displayId = is_scalar($requestId) ? (string) $requestId : '';

        return (new MailMessage())
            ->subject('Additional source documents submitted')
            ->line('Additional source documents have been submitted for request #' . $displayId . '.')
            ->line('Files: ' . $files)
            ->line('Uploaded by: ' . $requester->name)
            ->line('Requested by: ' . $openedBy->name)
            ->line('Stage: ' . $stage->name)
            ->line('Reason: ' . $this->documentRequest->reason)
            ->line('Submitted: ' . $this->documentRequest->submitted_at?->toDayDateTimeString())
            ->action('View request', tenant_route_url($this->domain, $route, $request));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        $subject = $this->documentRequest->instance?->workflowable;
        $requester = $this->documentRequest->requester;
        $openedBy = $this->documentRequest->openedBy;
        $instanceStage = $this->documentRequest->openingStage;
        $stage = $instanceStage?->stage;
        if ((! $subject instanceof PaymentRequest && ! $subject instanceof RetirementRequest) || $requester === null || $openedBy === null || $instanceStage === null || $stage === null) {
            throw new \LogicException('The document request notification context is incomplete.');
        }
        $route = $subject instanceof RetirementRequest ? 'retirement-requests.show' : 'payment-requests.show';

        return [
            'workflow_document_request_id' => $this->documentRequest->id,
            'attachment_names' => $this->documentRequest->attachments->pluck('original_name')->values()->all(),
            'uploader' => $requester->name,
            'authorizing_approver' => $openedBy->name,
            'stage' => $stage->name,
            'reason' => $this->documentRequest->reason,
            'submitted_at' => $this->documentRequest->submitted_at?->toISOString(),
            'request_url' => tenant_route_url($this->domain, $route, $subject),
        ];
    }
}
