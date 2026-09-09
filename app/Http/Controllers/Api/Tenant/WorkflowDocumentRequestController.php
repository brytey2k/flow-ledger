<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Http\Requests\Tenant\AttachmentUploadRequest;
use App\Http\Requests\Tenant\WorkflowDocumentRequestCancellationRequest;
use App\Http\Requests\Tenant\WorkflowDocumentRequestOpenRequest;
use App\Http\Requests\Tenant\WorkflowDocumentRequestResolutionRequest;
use App\Http\Requests\Tenant\WorkflowDocumentSubmissionRequest;
use App\Http\Requests\Tenant\WorkflowReferralResponseRequest;
use App\Http\Requests\Tenant\WorkflowReferralStoreRequest;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\WorkflowDocumentRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowReviewReferral;
use App\Services\WorkflowDocumentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class WorkflowDocumentRequestController extends BaseApiController
{
    public function __construct(private readonly WorkflowDocumentRequestService $service) {}

    public function open(WorkflowDocumentRequestOpenRequest $request, WorkflowInstanceStage $workflowInstanceStage): JsonResponse
    {
        $this->authorize(PermissionKey::ApproveRequests->value);
        $result = $this->service->open($workflowInstanceStage, $this->apiUser(), $request->reason());

        return response()->json(['data' => $result], 201);
    }

    public function upload(AttachmentUploadRequest $request, WorkflowDocumentRequest $documentRequest): JsonResponse
    {
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);

        return response()->json(['data' => $this->service->upload($documentRequest, $file, $this->apiUser())], 201);
    }

    public function delete(WorkflowDocumentRequest $documentRequest, Attachment $attachment): JsonResponse
    {
        $this->service->deleteAttachment($documentRequest, $attachment, $this->apiUser());

        return response()->json(null, 204);
    }

    public function submit(WorkflowDocumentSubmissionRequest $request, WorkflowDocumentRequest $documentRequest): JsonResponse
    {
        return response()->json(['data' => $this->service->submit($documentRequest, $this->apiUser())]);
    }

    public function refer(WorkflowReferralStoreRequest $request, WorkflowDocumentRequest $documentRequest): JsonResponse
    {
        $this->authorize(PermissionKey::ApproveRequests->value);
        $this->service->createReferrals($documentRequest, $this->apiUser(), $request->actionIds());

        return response()->json(['data' => $documentRequest->refresh()->load('referrals')]);
    }

    public function respond(WorkflowReferralResponseRequest $request, WorkflowReviewReferral $referral): JsonResponse
    {
        $this->service->respond($referral, $this->apiUser(), $request->decision(), $request->comment());

        return response()->json(['data' => $referral->refresh()]);
    }

    public function resolve(WorkflowDocumentRequestResolutionRequest $request, WorkflowDocumentRequest $documentRequest): JsonResponse
    {
        $this->authorize(PermissionKey::ApproveRequests->value);
        $next = $this->service->resolve($documentRequest, $this->apiUser(), $request->resolution(), $request->comment());

        return response()->json(['data' => $documentRequest->refresh(), 'next_document_request' => $next]);
    }

    public function cancel(WorkflowDocumentRequestCancellationRequest $request, WorkflowDocumentRequest $documentRequest): JsonResponse
    {
        $this->authorize(PermissionKey::EditWorkflowTemplate->value);
        $this->service->cancel($documentRequest, $this->apiUser(), $request->string('reason')->toString());

        return response()->json(['data' => $documentRequest->refresh()]);
    }
}
