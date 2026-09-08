<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Exceptions\SendBackNotAllowedException;
use App\Http\Requests\Tenant\ApprovalActionRequest;
use App\Http\Requests\Tenant\WorkflowStageRecoveryRequest;
use App\Models\Role;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Services\WorkflowEngineService;
use App\Services\WorkflowStageRecoveryService;
use Illuminate\Http\JsonResponse;

class ApprovalActionController extends BaseApiController
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
        private readonly WorkflowStageRecoveryService $recovery,
    ) {}

    public function approve(ApprovalActionRequest $request, WorkflowInstanceStage $workflowInstanceStage): JsonResponse
    {
        $this->authorize(PermissionKey::ApproveRequests->value);
        $user = $this->apiUser();

        abort_unless($this->engine->canUserActOnStage($workflowInstanceStage, $user), 403, 'You are not authorised to act on this stage.');
        abort_unless($workflowInstanceStage->isActive(), 422, 'This stage is no longer active.');

        $comment = $request->input('comment');
        $this->engine->approve($workflowInstanceStage, $user, is_string($comment) ? $comment : null);

        return response()->json(['data' => $workflowInstanceStage->refresh()->load('instance.workflowable')]);
    }

    public function reject(ApprovalActionRequest $request, WorkflowInstanceStage $workflowInstanceStage): JsonResponse
    {
        $this->authorize(PermissionKey::ApproveRequests->value);
        $user = $this->apiUser();

        abort_unless($this->engine->canUserActOnStage($workflowInstanceStage, $user), 403, 'You are not authorised to act on this stage.');
        abort_unless($workflowInstanceStage->isActive(), 422, 'This stage is no longer active.');

        $comment = $request->input('comment');
        $this->engine->reject($workflowInstanceStage, $user, is_string($comment) ? $comment : '');

        return response()->json(['data' => $workflowInstanceStage->refresh()->load('instance.workflowable')]);
    }

    public function sendBack(ApprovalActionRequest $request, WorkflowInstanceStage $workflowInstanceStage): JsonResponse
    {
        $this->authorize(PermissionKey::ApproveRequests->value);
        $user = $this->apiUser();

        abort_unless($this->engine->canUserActOnStage($workflowInstanceStage, $user), 403, 'You are not authorised to act on this stage.');
        abort_unless($workflowInstanceStage->isActive(), 422, 'This stage is no longer active.');

        $comment = $request->input('comment');

        try {
            $this->engine->sendBack($workflowInstanceStage, $user, is_string($comment) ? $comment : '');
        } catch (SendBackNotAllowedException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['data' => $workflowInstanceStage->refresh()->load('instance.workflowable')]);
    }

    public function retry(WorkflowInstanceStage $workflowInstanceStage): JsonResponse
    {
        $this->authorize(PermissionKey::EditWorkflowTemplate->value);

        abort_unless($workflowInstanceStage->isBlocked(), 422, 'This stage is not blocked.');
        abort_unless($this->engine->retryBlockedStage($workflowInstanceStage), 422, 'No independent approver is available.');

        return response()->json(['data' => $workflowInstanceStage->refresh()->load('instance.workflowable')]);
    }

    public function recover(
        WorkflowStageRecoveryRequest $request,
        WorkflowInstanceStage $workflowInstanceStage,
    ): JsonResponse {
        $this->authorize(PermissionKey::EditWorkflowTemplate->value);
        $role = Role::findOrFail($request->roleId());
        $activated = $this->recovery->applyAndRetry(
            $workflowInstanceStage,
            $role,
            $this->apiUser(),
            $request->reason(),
        );

        return response()->json([
            'data' => $workflowInstanceStage->refresh()->load('instance.workflowable'),
            'meta' => [
                'activated' => $activated,
                'template_update_required' => true,
            ],
        ], $activated ? 200 : 422);
    }
}
