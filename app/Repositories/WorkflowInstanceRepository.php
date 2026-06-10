<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class WorkflowInstanceRepository
{
    /** @return LengthAwarePaginator<int, WorkflowInstanceStage> */
    public function activeStagesForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $roleIds = $user->roles()->pluck('id');
        $staffBranchId = $user->staffProfile?->branch_id;
        $staffDepartmentId = $user->staffProfile?->department_id;

        return WorkflowInstanceStage::query()
            ->join('workflow_stages as ws', 'workflow_instance_stages.workflow_stage_id', '=', 'ws.id')
            ->join('workflow_instances as wi', 'workflow_instance_stages.workflow_instance_id', '=', 'wi.id')
            ->select('workflow_instance_stages.*')
            ->where('workflow_instance_stages.status', 'active')
            ->whereHas('stage.roles', fn($q) => $q->whereIn('roles.id', $roleIds))
            ->where(function ($q) use ($staffDepartmentId): void {
                $q->where('ws.scope_to_department', false)
                    ->orWhere(fn($inner) => $inner
                        ->where('ws.scope_to_department', true)
                        ->whereNotNull('wi.department_id')
                        ->where('wi.department_id', $staffDepartmentId));
            })
            ->where(function ($q) use ($staffBranchId): void {
                $q->where('ws.scope_to_branch', false)
                    ->orWhere(fn($inner) => $inner
                        ->where('ws.scope_to_branch', true)
                        ->whereNotNull('wi.branch_id')
                        ->where('wi.branch_id', $staffBranchId));
            })
            ->with([
                'stage',
                'instance.workflowable.staff',
                'instance.workflowable.branch',
                'instance.workflowable.currency',
            ])
            ->latest('workflow_instance_stages.created_at')
            ->paginate($perPage);
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return EloquentCollection<int, WorkflowInstanceStage>
     */
    public function approvalTurnaroundStages(string $dateFrom, string $dateTo): EloquentCollection
    {
        return WorkflowInstanceStage::with('stage')
            ->whereIn('status', ['approved', 'sent_back', 'cancelled'])
            ->whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->whereBetween('completed_at', [$dateFrom, $dateTo])
            ->get();
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return EloquentCollection<int, WorkflowInstanceStage>
     */
    public function retirementTurnaroundStages(string $dateFrom, string $dateTo): EloquentCollection
    {
        return WorkflowInstanceStage::with('stage')
            ->whereHas('instance', fn($q) => $q->where('workflowable_type', RetirementRequest::class))
            ->whereIn('status', ['approved', 'sent_back', 'cancelled'])
            ->whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->whereBetween('completed_at', [$dateFrom, $dateTo])
            ->get();
    }

    /** @return EloquentCollection<int, WorkflowInstanceStage> */
    public function activeRequestStages(): EloquentCollection
    {
        return WorkflowInstanceStage::with([
            'stage',
            'instance.workflowable',
            'instance.workflowable.staff',
            'instance.workflowable.branch',
            'instance.workflowable.currency',
        ])
            ->where('status', 'active')
            ->whereNotNull('started_at')
            ->orderBy('started_at')
            ->get();
    }
}
