<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class WorkflowInstanceRepository
{
    public function __construct(private readonly WorkflowApproverRepository $approvers) {}

    public function pendingApprovalsCountForUser(User $user): int
    {
        return $this->approvers->eligibleStagesForUser($user)->count();
    }

    /**
     * @param array<int, int> $branchIds
     *
     * @return Collection<int, array{bucket: string, count: int}>
     */
    public function approvalAgingBuckets(array $branchIds): Collection
    {
        $now = now()->toDateTimeString();

        /** @var Collection<int, object{bucket: string, count: int|float|string}> $rows */
        $rows = DB::table('workflow_instance_stages')
            ->join('workflow_instances', 'workflow_instances.id', '=', 'workflow_instance_stages.workflow_instance_id')
            ->where('workflow_instance_stages.status', 'active')
            ->whereIn('workflow_instances.branch_id', $branchIds)
            ->whereNotNull('workflow_instance_stages.started_at')
            ->selectRaw("CASE
                WHEN workflow_instance_stages.started_at >= (?::timestamp - INTERVAL '3 days') THEN '0-3 days'
                WHEN workflow_instance_stages.started_at >= (?::timestamp - INTERVAL '7 days') THEN '4-7 days'
                ELSE '8+ days'
            END AS bucket, COUNT(*) as count", [$now, $now])
            ->groupBy('bucket')
            ->get();

        $map = collect([
            ['bucket' => '0-3 days', 'count' => 0],
            ['bucket' => '4-7 days', 'count' => 0],
            ['bucket' => '8+ days', 'count' => 0],
        ])->keyBy('bucket');

        $rows->each(function (object $row) use ($map): void {
            $bucket = (string) $row->bucket;
            if ($map->has($bucket)) {
                $map->put($bucket, [
                    'bucket' => $bucket,
                    'count' => (int) $row->count,
                ]);
            }
        });

        /** @var Collection<int, array{bucket: string, count: int}> $result */
        $result = $map->values();

        return $result;
    }

    /**
     * @param array<int, int> $branchIds
     * @param int $days
     */
    public function sendBackRateInLastDays(array $branchIds, int $days = 30): float
    {
        $startDate = now()->subDays($days)->toDateString();

        /** @var int $sentBack */
        $sentBack = DB::table('workflow_instance_stages')
            ->join('workflow_instances', 'workflow_instances.id', '=', 'workflow_instance_stages.workflow_instance_id')
            ->whereIn('workflow_instances.branch_id', $branchIds)
            ->whereDate('workflow_instance_stages.updated_at', '>=', $startDate)
            ->where('workflow_instance_stages.status', 'sent_back')
            ->count();

        /** @var int $resolved */
        $resolved = DB::table('workflow_instance_stages')
            ->join('workflow_instances', 'workflow_instances.id', '=', 'workflow_instance_stages.workflow_instance_id')
            ->whereIn('workflow_instances.branch_id', $branchIds)
            ->whereDate('workflow_instance_stages.updated_at', '>=', $startDate)
            ->whereIn('workflow_instance_stages.status', ['approved', 'sent_back', 'rejected'])
            ->count();

        if ($resolved === 0) {
            return 0.0;
        }

        return round(($sentBack / $resolved) * 100, 1);
    }

    /** @return LengthAwarePaginator<int, WorkflowInstanceStage> */
    public function activeStagesForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        return $this->approvers->eligibleStagesForUser($user)
            ->with([
                'stage.roles',
                'stage.fallbackRoles',
                'instance.workflowable.staff',
                'instance.workflowable.branch',
                'instance.workflowable.currency',
            ])
            ->latest('workflow_instance_stages.created_at')
            ->orderByDesc('workflow_instance_stages.id')
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
