<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\WorkflowInstanceStage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApprovalController extends BaseApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize(PermissionKey::ApproveRequests->value);
        $user = $this->apiUser();
        $perPage = min((int) $request->query('per_page', 20), 50);

        $roleIds = $user->roles->pluck('id')->toArray();

        $stages = WorkflowInstanceStage::query()
            ->whereHas('stage.roles', fn($q) => $q->whereIn('roles.id', $roleIds))
            ->where('status', 'active')
            ->with([
                'instance.workflowable',
                'stage.roles',
            ])
            ->latest()
            ->paginate($perPage);

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

        $workflowInstanceStage->load([
            'instance.workflowable',
            'stage.roles',
            'actions.user',
        ]);

        return response()->json(['data' => $workflowInstanceStage]);
    }
}
