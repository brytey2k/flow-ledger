<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\WorkflowInstanceRepository;
use App\Services\WorkflowApproverResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends BaseApiController
{
    public function __construct(
        private readonly WorkflowInstanceRepository $instances,
        private readonly WorkflowApproverResolver $approvers,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize(PermissionKey::ApproveRequests->value);
        $user = $this->apiUser();
        $perPage = min((int) $request->query('per_page', 20), 50);

        $stages = $this->instances->activeStagesForUser($user, $perPage);

        return response()->json([
            'data' => $stages->items(),
            'meta' => [
                'current_page' => $stages->currentPage(),
                'last_page' => $stages->lastPage(),
                'per_page' => $stages->perPage(),
                'total' => $stages->total(),
            ],
        ]);
    }

    public function show(WorkflowInstanceStage $workflowInstanceStage): JsonResponse
    {
        $this->authorize(PermissionKey::ApproveRequests->value);
        $user = $this->apiUser();

        abort_unless($this->approvers->canAct($workflowInstanceStage, $user), 403, 'You are not authorised to view this stage.');

        $workflowInstanceStage->load([
            'instance.workflowable',
            'stage.roles',
            'stage.fallbackRoles',
            'actions.user',
        ]);

        return response()->json(['data' => $workflowInstanceStage]);
    }
}
