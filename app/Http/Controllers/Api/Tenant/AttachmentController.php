<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Requests\Tenant\AttachmentUploadRequest;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\UploadedFile;

class AttachmentController extends BaseApiController
{
    public function __construct(private readonly AttachmentService $service) {}

    public function storeForPaymentRequest(AttachmentUploadRequest $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        $branchIds = $this->resolveAllowedBranchIds($user);
        abort_unless(in_array($paymentRequest->branch_id, $branchIds, true), 403);

        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);
        $attachment = $this->service->storeEditableRequestAttachment($paymentRequest, $file, $user);

        return response()->json(['data' => $attachment], 201);
    }

    public function storeForRetirementRequest(AttachmentUploadRequest $request, RetirementRequest $retirementRequest): JsonResponse
    {
        $user = $this->apiUser();
        $branchIds = $this->resolveAllowedBranchIds($user);
        $paymentBranchId = $retirementRequest->paymentRequest?->branch_id;
        abort_unless($paymentBranchId !== null && in_array($paymentBranchId, $branchIds, true), 403);
        $file = $request->file('file');
        abort_unless($file instanceof UploadedFile, 422);
        $attachment = $this->service->storeEditableRequestAttachment($retirementRequest, $file, $user);

        return response()->json(['data' => $attachment], 201);
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        $user = $this->apiUser();
        $this->service->deleteEditableRequestAttachment($attachment, $user);

        return response()->json(null, 204);
    }

    /** @return list<int> */
    private function resolveAllowedBranchIds(\App\Models\Tenant\User $user): array
    {
        return app(\App\Services\BranchScopeService::class)->allowedBranchIds($user);
    }
}
