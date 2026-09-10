<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\UserStatus;
use App\Enums\Tenant\WorkflowDocumentRequestStatus;
use App\Enums\Tenant\WorkflowReviewDecision;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowDocumentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowReviewReferral;
use App\Notifications\WorkflowDocumentsSubmittedNotification;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class WorkflowDocumentRequestService
{
    public function __construct(
        private readonly WorkflowApproverResolver $approvers,
        private readonly AttachmentService $attachments,
        private readonly WorkflowEngineService $engine,
    ) {}

    public function open(WorkflowInstanceStage $instanceStage, User $approver, string $reason): WorkflowDocumentRequest
    {
        try {
            return DB::transaction(function () use ($instanceStage, $approver, $reason): WorkflowDocumentRequest {
                $instance = WorkflowInstance::lockForUpdate()->findOrFail($instanceStage->workflow_instance_id);
                $stage = WorkflowInstanceStage::with('stage')->lockForUpdate()->findOrFail($instanceStage->id);

                if (! $instance->isInProgress() || ! $stage->isActive()) {
                    throw new ConflictHttpException('This approval stage is no longer active.');
                }
                if (blank($reason)) {
                    throw new UnprocessableEntityHttpException('A document request reason is required.');
                }
                $stageDefinition = $this->stageDefinition($stage);
                if (! $stageDefinition->allow_document_requests) {
                    throw new AuthorizationException('Document requests are not permitted at this stage.');
                }
                if ($instance->hasDocumentHold()) {
                    throw new ConflictHttpException('This workflow already has an unresolved document request.');
                }
                if (! $this->approvers->canAct($stage, $approver)) {
                    throw new AuthorizationException('You are not authorised to act on this stage.');
                }

                $subject = $this->supportedSubject($instance);
                if ($instance->submitter_user_id === null) {
                    throw new UnprocessableEntityHttpException('The workflow requester could not be identified.');
                }

                $this->approvers->claim($stage, $approver);
                $documentRequest = WorkflowDocumentRequest::create([
                    'workflow_instance_id' => $instance->id,
                    'opening_instance_stage_id' => $stage->id,
                    'requester_user_id' => $instance->submitter_user_id,
                    'opened_by_user_id' => $approver->id,
                    'status' => WorkflowDocumentRequestStatus::AwaitingUploads,
                    'reason' => $reason,
                ]);

                activity()->performedOn($subject)->causedBy($approver)
                    ->event('document_request.opened')
                    ->withProperties(['document_request_id' => $documentRequest->id, 'stage' => $stageDefinition->name, 'reason' => $reason])
                    ->log('Additional source documents requested');

                return $documentRequest;
            });
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23505' && str_contains($exception->getMessage(), 'workflow_document_requests_one_unresolved')) {
                throw new ConflictHttpException('This workflow already has an unresolved document request.', $exception);
            }

            throw $exception;
        }
    }

    public function upload(WorkflowDocumentRequest $documentRequest, UploadedFile $file, User $user): Attachment
    {
        return DB::transaction(function () use ($documentRequest, $file, $user): Attachment {
            [$instance, $stage, $fresh] = $this->lockedRequest($documentRequest);
            $this->authorizeOwnerUpload($fresh, $user);
            $subject = $this->supportedSubject($instance);
            $attachment = $this->attachments->store($subject, $file, $user, $fresh);
            $stageDefinition = $this->stageDefinition($stage);

            activity()->performedOn($subject)->causedBy($user)
                ->event('document_request.attachment_added')
                ->withProperties(['document_request_id' => $fresh->id, 'attachment_id' => $attachment->id, 'name' => $attachment->original_name, 'stage' => $stageDefinition->name])
                ->log('Additional source document uploaded');

            return $attachment;
        });
    }

    public function deleteAttachment(WorkflowDocumentRequest $documentRequest, Attachment $attachment, User $user): void
    {
        DB::transaction(function () use ($documentRequest, $attachment, $user): void {
            [$instance, $stage, $fresh] = $this->lockedRequest($documentRequest);
            $this->authorizeOwnerUpload($fresh, $user);
            $lockedAttachment = Attachment::lockForUpdate()->findOrFail($attachment->id);
            if ($lockedAttachment->workflow_document_request_id !== $fresh->id || $lockedAttachment->user_id !== $user->id) {
                throw new AuthorizationException('Only current-window attachments uploaded by you may be deleted.');
            }

            $name = $lockedAttachment->original_name;
            $this->attachments->delete($lockedAttachment);
            $stageDefinition = $this->stageDefinition($stage);
            activity()->performedOn($this->supportedSubject($instance))->causedBy($user)
                ->event('document_request.attachment_removed')
                ->withProperties(['document_request_id' => $fresh->id, 'name' => $name, 'stage' => $stageDefinition->name])
                ->log('Additional source document removed');
        });
    }

    public function submit(WorkflowDocumentRequest $documentRequest, User $user): WorkflowDocumentRequest
    {
        $submitted = DB::transaction(function () use ($documentRequest, $user): WorkflowDocumentRequest {
            [$instance, $stage, $fresh] = $this->lockedRequest($documentRequest);
            $this->authorizeOwnerUpload($fresh, $user);
            if (! $fresh->attachments()->exists()) {
                throw new UnprocessableEntityHttpException('At least one new attachment is required.');
            }

            $fresh->update(['status' => WorkflowDocumentRequestStatus::Submitted, 'submitted_at' => now()]);
            $stageDefinition = $this->stageDefinition($stage);
            activity()->performedOn($this->supportedSubject($instance))->causedBy($user)
                ->event('document_request.documents_submitted')
                ->withProperties([
                    'document_request_id' => $fresh->id,
                    'stage' => $stageDefinition->name,
                    'attachments' => $fresh->attachments()->pluck('original_name')->all(),
                ])->log('Additional source documents submitted');

            return $fresh->load(['attachments', 'requester', 'openedBy', 'openingStage.stage', 'instance.workflowable']);
        });

        $recipients = $this->submissionRecipients($submitted);
        Notification::send($recipients, new WorkflowDocumentsSubmittedNotification($submitted));

        return $submitted;
    }

    /** @return Collection<int, WorkflowAction> */
    public function eligibleReferralActions(WorkflowDocumentRequest $documentRequest): Collection
    {
        $documentRequest->loadMissing('openingStage.stage');
        $openingStage = $documentRequest->openingStage;
        if ($openingStage === null) {
            throw new UnprocessableEntityHttpException('The opening workflow stage is unavailable.');
        }
        $openingStageDefinition = $this->stageDefinition($openingStage);

        return WorkflowAction::query()
            ->with(['user', 'instanceStage.stage'])
            ->where('action', 'approve')
            ->whereHas('instanceStage', fn($query) => $query
                ->where('workflow_instance_id', $documentRequest->workflow_instance_id)
                ->where('status', 'approved')
                ->whereHas('stage', fn($stageQuery) => $stageQuery
                    ->where('display_order', '<', $openingStageDefinition->display_order)))
            ->whereHas('user', fn($query) => $query->where('status', UserStatus::Active->value))
            ->orderBy('created_at')
            ->get();
    }

    /** @param list<int> $actionIds */
    public function createReferrals(WorkflowDocumentRequest $documentRequest, User $user, array $actionIds): void
    {
        DB::transaction(function () use ($documentRequest, $user, $actionIds): void {
            [$instance, $stage, $fresh] = $this->lockedRequest($documentRequest);
            $this->authorizeOpeningApprover($fresh, $stage, $user);
            if ($fresh->status !== WorkflowDocumentRequestStatus::Submitted) {
                throw new ConflictHttpException('Referrals may only be created after documents are submitted.');
            }

            $eligible = $this->eligibleReferralActions($fresh)->keyBy('id');
            foreach ($actionIds as $actionId) {
                /** @var WorkflowAction|null $action */
                $action = $eligible->get($actionId);
                if ($action === null) {
                    throw new AuthorizationException('A selected reviewer did not approve an earlier stage of this workflow.');
                }

                WorkflowReviewReferral::create([
                    'workflow_document_request_id' => $fresh->id,
                    'referring_instance_stage_id' => $stage->id,
                    'referred_workflow_action_id' => $action->id,
                    'referred_instance_stage_id' => $action->workflow_instance_stage_id,
                    'referred_by_user_id' => $user->id,
                    'reviewer_user_id' => $action->user_id,
                ]);
            }
            $fresh->update(['status' => WorkflowDocumentRequestStatus::AwaitingReReview]);
            activity()->performedOn($this->supportedSubject($instance))->causedBy($user)
                ->event('document_request.referral_created')
                ->withProperties(['document_request_id' => $fresh->id, 'workflow_action_ids' => $actionIds])
                ->log('Additional documents referred for advisory review');
        });
    }

    public function respond(WorkflowReviewReferral $referral, User $user, string $decision, string|null $comment): void
    {
        DB::transaction(function () use ($referral, $user, $decision, $comment): void {
            $requestId = $referral->workflow_document_request_id;
            $requestSnapshot = WorkflowDocumentRequest::findOrFail($requestId);
            $instance = WorkflowInstance::lockForUpdate()->findOrFail($requestSnapshot->workflow_instance_id);
            WorkflowInstanceStage::lockForUpdate()->findOrFail($requestSnapshot->opening_instance_stage_id);
            $request = WorkflowDocumentRequest::lockForUpdate()->findOrFail($requestId);
            $fresh = WorkflowReviewReferral::lockForUpdate()->findOrFail($referral->id);

            if ($request->status !== WorkflowDocumentRequestStatus::AwaitingReReview || $fresh->responded_at !== null) {
                throw new ConflictHttpException('This referral is no longer actionable.');
            }
            if (WorkflowReviewDecision::tryFrom($decision) === null) {
                throw new UnprocessableEntityHttpException('The advisory decision is invalid.');
            }
            if ($fresh->reviewer_user_id !== $user->id || $user->status !== UserStatus::Active) {
                throw new AuthorizationException('You are not authorised to respond to this referral.');
            }
            if ($decision === WorkflowReviewDecision::Concern->value && blank($comment)) {
                throw new UnprocessableEntityHttpException('A comment is required when raising a concern.');
            }

            $fresh->update(['decision' => $decision, 'comment' => $comment, 'responded_at' => now()]);
            activity()->performedOn($this->supportedSubject($instance))->causedBy($user)
                ->event('document_request.referral_responded')
                ->withProperties(['document_request_id' => $request->id, 'referral_id' => $fresh->id, 'decision' => $decision, 'comment' => $comment])
                ->log('Advisory document review completed');
        });
    }

    public function resolve(WorkflowDocumentRequest $documentRequest, User $user, string $resolution, string|null $comment): WorkflowDocumentRequest|null
    {
        return DB::transaction(function () use ($documentRequest, $user, $resolution, $comment): WorkflowDocumentRequest|null {
            [$instance, $stage, $fresh] = $this->lockedRequest($documentRequest, true);
            $this->authorizeOpeningApprover($fresh, $stage, $user);
            if (! in_array($resolution, ['resume', 'request_more_documents', 'send_back', 'reject', 'override'], true)) {
                throw new UnprocessableEntityHttpException('The document request resolution is invalid.');
            }
            if ($fresh->status === WorkflowDocumentRequestStatus::AwaitingUploads) {
                throw new ConflictHttpException('Documents have not been submitted.');
            }
            if ($fresh->referrals()->whereNull('responded_at')->exists()) {
                throw new ConflictHttpException('All advisory reviewers must respond first.');
            }

            $hasConcern = $fresh->referrals()->where('decision', WorkflowReviewDecision::Concern->value)->exists();
            if ($hasConcern && ! in_array($resolution, ['request_more_documents', 'send_back', 'reject', 'override'], true)) {
                throw new UnprocessableEntityHttpException('A concern resolution must be selected.');
            }
            if (in_array($resolution, ['request_more_documents', 'send_back', 'reject', 'override'], true) && blank($comment)) {
                throw new UnprocessableEntityHttpException('A resolution comment is required.');
            }
            if (! $hasConcern && in_array($resolution, ['request_more_documents', 'send_back', 'reject', 'override'], true)) {
                throw new UnprocessableEntityHttpException('This resolution is only available when a reviewer raised a concern.');
            }

            $fresh->update([
                'status' => WorkflowDocumentRequestStatus::Resolved,
                'resolution' => $resolution,
                'resolution_comment' => $comment,
                'resolved_at' => now(),
            ]);

            $subject = $this->supportedSubject($instance);
            if ($resolution === 'override') {
                activity()->performedOn($subject)->causedBy($user)->event('document_request.concern_overridden')
                    ->withProperties(['document_request_id' => $fresh->id, 'justification' => $comment])
                    ->log('Document review concern overridden');
            }
            if (in_array($resolution, ['resume', 'override'], true)) {
                activity()->performedOn($subject)->causedBy($user)->event('document_request.review_resumed')
                    ->withProperties(['document_request_id' => $fresh->id, 'resolution' => $resolution])
                    ->log('Approval review resumed');
            }

            if ($resolution === 'request_more_documents') {
                $next = WorkflowDocumentRequest::create([
                    'workflow_instance_id' => $instance->id,
                    'opening_instance_stage_id' => $stage->id,
                    'requester_user_id' => $fresh->requester_user_id,
                    'opened_by_user_id' => $user->id,
                    'parent_document_request_id' => $fresh->id,
                    'status' => WorkflowDocumentRequestStatus::AwaitingUploads,
                    'reason' => (string) $comment,
                ]);

                activity()->performedOn($subject)->causedBy($user)->event('document_request.opened')
                    ->withProperties(['document_request_id' => $next->id, 'parent_document_request_id' => $fresh->id, 'reason' => $comment])
                    ->log('Further source documents requested');

                return $next;
            }
            if ($resolution === 'send_back') {
                $this->engine->sendBack($stage, $user, (string) $comment);
            } elseif ($resolution === 'reject') {
                $this->engine->reject($stage, $user, (string) $comment);
            }

            return null;
        });
    }

    public function cancel(WorkflowDocumentRequest $documentRequest, User $administrator, string $reason): void
    {
        DB::transaction(function () use ($documentRequest, $administrator, $reason): void {
            if (! $administrator->can(\App\Enums\Tenant\PermissionKey::EditWorkflowTemplate->value)) {
                throw new AuthorizationException('Only workflow administrators may cancel a document request.');
            }
            if (blank($reason)) {
                throw new UnprocessableEntityHttpException('An administrative cancellation reason is required.');
            }
            [$instance, , $fresh] = $this->lockedRequest($documentRequest, true);
            $fresh->update([
                'status' => WorkflowDocumentRequestStatus::Cancelled,
                'resolution' => 'administrative_cancel',
                'resolution_comment' => $reason,
                'cancelled_at' => now(),
                'cancelled_by_user_id' => $administrator->id,
            ]);
            activity()->performedOn($this->supportedSubject($instance))->causedBy($administrator)
                ->event('document_request.cancelled')
                ->withProperties(['document_request_id' => $fresh->id, 'reason' => $reason])
                ->log('Document request cancelled by an administrator');
        });
    }

    /** @return Collection<int, WorkflowReviewReferral> */
    public function actionableReferrals(User $user): Collection
    {
        return WorkflowReviewReferral::with(['documentRequest.instance.workflowable', 'documentRequest.openingStage.stage', 'referredStage.stage'])
            ->where('reviewer_user_id', $user->id)
            ->whereNull('responded_at')
            ->whereHas('documentRequest', fn($query) => $query->where('status', WorkflowDocumentRequestStatus::AwaitingReReview->value))
            ->latest()
            ->orderByDesc('id')
            ->get();
    }

    /** @return array{WorkflowInstance, WorkflowInstanceStage, WorkflowDocumentRequest} */
    private function lockedRequest(WorkflowDocumentRequest $documentRequest, bool $lockReferrals = false): array
    {
        $snapshot = WorkflowDocumentRequest::findOrFail($documentRequest->id);
        $instance = WorkflowInstance::lockForUpdate()->findOrFail($snapshot->workflow_instance_id);
        $stage = WorkflowInstanceStage::with('stage')->lockForUpdate()->findOrFail($snapshot->opening_instance_stage_id);
        $fresh = WorkflowDocumentRequest::lockForUpdate()->findOrFail($snapshot->id);
        if (! $fresh->isUnresolved()) {
            throw new ConflictHttpException('This document request is already resolved.');
        }
        if ($lockReferrals) {
            $fresh->referrals()->lockForUpdate()->get();
        }

        return [$instance, $stage, $fresh];
    }

    private function authorizeOwnerUpload(WorkflowDocumentRequest $request, User $user): void
    {
        if ($request->status !== WorkflowDocumentRequestStatus::AwaitingUploads) {
            throw new ConflictHttpException('This upload window is closed.');
        }
        if ($request->requester_user_id !== $user->id) {
            throw new AuthorizationException('Only the request owner may upload documents.');
        }
    }

    private function authorizeOpeningApprover(WorkflowDocumentRequest $request, WorkflowInstanceStage $stage, User $user): void
    {
        if ($request->opened_by_user_id !== $user->id || ! $stage->isActive()) {
            throw new AuthorizationException('Only the approver who opened this window may continue the review.');
        }
    }

    private function supportedSubject(WorkflowInstance $instance): PaymentRequest|RetirementRequest
    {
        $subject = $instance->workflowable;
        if ($subject instanceof PaymentRequest && ! $subject->isExpense()) {
            throw new UnprocessableEntityHttpException('Document requests are only supported for expense requests and retirements.');
        }
        if (! $subject instanceof PaymentRequest && ! $subject instanceof RetirementRequest) {
            throw new UnprocessableEntityHttpException('This workflow does not support document requests.');
        }

        return $subject;
    }

    private function stageDefinition(WorkflowInstanceStage $instanceStage): \App\Models\Tenant\WorkflowStage
    {
        $stage = $instanceStage->stage;
        if ($stage === null) {
            throw new UnprocessableEntityHttpException('The workflow stage definition is unavailable.');
        }

        return $stage;
    }

    /** @return Collection<int, User> */
    private function submissionRecipients(WorkflowDocumentRequest $request): Collection
    {
        $userIds = WorkflowAction::query()
            ->where('action', 'approve')
            ->whereHas('instanceStage', fn($query) => $query->where('workflow_instance_id', $request->workflow_instance_id))
            ->pluck('user_id')
            ->push($request->opened_by_user_id)
            ->unique();

        return User::query()
            ->active()
            ->whereIn('id', $userIds)
            ->get();
    }
}
