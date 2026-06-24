<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Http\Requests\Tenant\ApprovalActionRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Services\WorkflowEngineService;
use Illuminate\Http\JsonResponse;

class ApprovalActionController extends BaseApiController
{
    public function __construct(private readonly WorkflowEngineService $engine) {}

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
        $this->engine->sendBack($workflowInstanceStage, $user, is_string($comment) ? $comment : '');

        return response()->json(['data' => $workflowInstanceStage->refresh()->load('instance.workflowable')]);
    }
}
