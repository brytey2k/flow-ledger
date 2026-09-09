<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AttachmentUploadRequest;
use App\Http\Requests\Tenant\WorkflowDocumentRequestCancellationRequest;
use App\Http\Requests\Tenant\WorkflowDocumentRequestOpenRequest;
use App\Http\Requests\Tenant\WorkflowDocumentRequestResolutionRequest;
use App\Http\Requests\Tenant\WorkflowDocumentSubmissionRequest;
use App\Http\Requests\Tenant\WorkflowReferralResponseRequest;
use App\Http\Requests\Tenant\WorkflowReferralStoreRequest;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowDocumentRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowReviewReferral;
use App\Services\WorkflowDocumentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;

class WorkflowDocumentRequestsController extends Controller
{
    public function __construct(private readonly WorkflowDocumentRequestService $service) {}

    public function open(WorkflowDocumentRequestOpenRequest $request, WorkflowInstanceStage $instanceStage): RedirectResponse
    {
        $this->service->open($instanceStage, $this->user($request), $request->reason());

        return back()->with('success', 'The attachment-only upload window is open.');
    }

    public function upload(AttachmentUploadRequest $request, WorkflowDocumentRequest $documentRequest): RedirectResponse
    {
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);
        $this->service->upload($documentRequest, $file, $this->user($request));

        return back()->with('success', 'Document uploaded.');
    }

    public function delete(WorkflowDocumentRequest $documentRequest, Attachment $attachment): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();
        $this->service->deleteAttachment($documentRequest, $attachment, $user);

        return back()->with('success', 'Document removed.');
    }

    public function submit(WorkflowDocumentSubmissionRequest $request, WorkflowDocumentRequest $documentRequest): RedirectResponse
    {
        $this->service->submit($documentRequest, $this->user($request));

        return back()->with('success', 'Documents submitted to the approver.');
    }

    public function refer(WorkflowReferralStoreRequest $request, WorkflowDocumentRequest $documentRequest): RedirectResponse
    {
        $this->service->createReferrals($documentRequest, $this->user($request), $request->actionIds());

        return back()->with('success', 'Advisory reviewers notified.');
    }

    public function respond(WorkflowReferralResponseRequest $request, WorkflowReviewReferral $referral): RedirectResponse
    {
        $this->service->respond($referral, $this->user($request), $request->decision(), $request->comment());

        return back()->with('success', 'Your advisory review was recorded.');
    }

    public function resolve(WorkflowDocumentRequestResolutionRequest $request, WorkflowDocumentRequest $documentRequest): RedirectResponse
    {
        $this->service->resolve($documentRequest, $this->user($request), $request->resolution(), $request->comment());

        return redirect($this->requestUrl($documentRequest))->with('success', 'The document hold was resolved.');
    }

    public function cancel(WorkflowDocumentRequestCancellationRequest $request, WorkflowDocumentRequest $documentRequest): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        abort_unless($user->can(PermissionKey::EditWorkflowTemplate->value), 403);
        $this->service->cancel($documentRequest, $user, $request->string('reason')->toString());

        return back()->with('success', 'The abandoned upload window was cancelled.');
    }

    private function user(\Illuminate\Http\Request $request): User
    {
        /** @var User $user */
        $user = $request->user();

        return $user;
    }

    private function requestUrl(WorkflowDocumentRequest $documentRequest): string
    {
        $instance = $documentRequest->instance;
        abort_unless($instance !== null, 404);
        $subject = $instance->workflowable;

        return $subject instanceof RetirementRequest
            ? route('retirement-requests.show', $subject)
            : route('payment-requests.show', $subject);
    }
}
