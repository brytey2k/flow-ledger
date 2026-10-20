<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Requests\Tenant\CommentStoreRequest;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Services\BranchScopeService;
use App\Services\WorkflowEngineService;
use Illuminate\Http\JsonResponse;

class CommentController extends BaseApiController
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
        private readonly WorkflowEngineService $workflowEngine,
    ) {}

    public function storeForPaymentRequest(CommentStoreRequest $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        $branchIds = $this->branchScope->allowedBranchIds($user);
        abort_unless(in_array($paymentRequest->branch_id, $branchIds, true), 403);
        abort_unless($this->canComment($paymentRequest, $paymentRequest->staff_id, $user), 403);

        $dto = $request->toDto();

        $comment = $paymentRequest->comments()->create([
            'user_id' => $user->id,
            'body' => $dto->body,
        ]);

        return response()->json(['data' => $comment->load('user')], 201);
    }

    public function storeForRetirementRequest(CommentStoreRequest $request, RetirementRequest $retirementRequest): JsonResponse
    {
        $user = $this->apiUser();
        $branchIds = $this->branchScope->allowedBranchIds($user);
        $paymentBranchId = $retirementRequest->paymentRequest?->branch_id;
        abort_unless($paymentBranchId !== null && in_array($paymentBranchId, $branchIds, true), 403);
        abort_unless($this->canComment($retirementRequest, $retirementRequest->paymentRequest->staff_id, $user), 403);

        $dto = $request->toDto();

        $comment = $retirementRequest->comments()->create([
            'user_id' => $user->id,
            'body' => $dto->body,
        ]);

        return response()->json(['data' => $comment->load('user')], 201);
    }

    /** Same rule as the web route: the request owner, or someone in its approval chain. */
    private function canComment(PaymentRequest|RetirementRequest $request, int|null $ownerStaffId, User $user): bool
    {
        if ($ownerStaffId !== null && $user->staffProfile?->id === $ownerStaffId) {
            return true;
        }

        return $this->workflowEngine->userIsInApprovalChain($request, $user);
    }
}
