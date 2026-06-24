<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Services\AttachmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttachmentController extends BaseApiController
{
    public function __construct(private readonly AttachmentService $service) {}

    public function storeForPaymentRequest(Request $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $user = $this->apiUser();
        $branchIds = $this->resolveAllowedBranchIds($user);
        abort_unless(in_array($paymentRequest->branch_id, $branchIds, true), 403);

        $attachment = $this->service->store($paymentRequest, $request->file('file'), $user);

        return response()->json(['data' => $attachment], 201);
    }

    public function storeForRetirementRequest(Request $request, RetirementRequest $retirementRequest): JsonResponse
    {
        $request->validate(['file' => ['required', 'file', 'max:10240']]);

        $user = $this->apiUser();
        $attachment = $this->service->store($retirementRequest, $request->file('file'), $user);

        return response()->json(['data' => $attachment], 201);
    }

    public function destroy(Attachment $attachment): JsonResponse
    {
        $user = $this->apiUser();
        abort_unless($attachment->user_id === $user->id, 403, 'You may only delete your own attachments.');

        $this->service->delete($attachment);

        return response()->json(null, 204);
    }

    /** @return list<int> */
    private function resolveAllowedBranchIds(\App\Models\Tenant\User $user): array
    {
        return app(\App\Services\BranchScopeService::class)->allowedBranchIds($user);
    }
}
