<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\BranchRepository;
use App\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseApiController
{
    public function __construct(
        private readonly BranchScopeService $branchScope,
        private readonly BranchRepository $branchRepository,
    ) {}

    public function __invoke(): JsonResponse
    {
        $user = $this->apiUser();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);
        $staffId = $user->staffProfile?->id;

        $pendingApprovals = $this->countPendingApprovals($user);
        $myDraftRequests = $this->countMyRequests($staffId, 'draft');
        $myInWorkflowRequests = $this->countMyRequests($staffId, 'in_workflow');
        $myDraftRetirements = $this->countMyRetirements($staffId, 'draft');
        $pendingDisbursements = PaymentRequest::whereIn('branch_id', $allowedBranchIds)
            ->where('status', 'approved')
            ->count();
        $lowCashBranches = $this->getLowCashBranches($user);

        return response()->json([
            'data' => [
                'pending_approvals' => $pendingApprovals,
                'my_draft_requests' => $myDraftRequests,
                'my_in_workflow_requests' => $myInWorkflowRequests,
                'my_draft_retirements' => $myDraftRetirements,
                'pending_disbursements' => $pendingDisbursements,
                'low_cash_branches' => $lowCashBranches,
            ],
        ]);
    }

    private function countPendingApprovals(\App\Models\Tenant\User $user): int
    {
        $roleIds = $user->roles()->pluck('id');
        $staffProfile = $user->staffProfile;
        $staffBranchId = $staffProfile?->branch_id;
        $staffDepartmentId = $staffProfile?->department_id;

        return WorkflowInstanceStage::query()
            ->join('workflow_stages as ws', 'workflow_instance_stages.workflow_stage_id', '=', 'ws.id')
            ->join('workflow_instances as wi', 'workflow_instance_stages.workflow_instance_id', '=', 'wi.id')
            ->where('workflow_instance_stages.status', 'active')
            ->whereHas('stage.roles', fn($q) => $q->whereIn('roles.id', $roleIds))
            ->when($staffProfile !== null, function ($q) use ($staffDepartmentId): void {
                $q->where(fn($inner) => $inner
                    ->where('ws.scope_to_department', false)
                    ->orWhere(fn($d) => $d
                        ->where('ws.scope_to_department', true)
                        ->whereNotNull('wi.department_id')
                        ->where('wi.department_id', $staffDepartmentId)));
            })
            ->when($staffProfile !== null, function ($q) use ($staffBranchId): void {
                $q->where(fn($inner) => $inner
                    ->where('ws.scope_to_branch', false)
                    ->orWhere(fn($b) => $b
                        ->where('ws.scope_to_branch', true)
                        ->whereNotNull('wi.branch_id')
                        ->where('wi.branch_id', $staffBranchId)));
            })
            ->count();
    }

    private function countMyRequests(int|null $staffId, string $status): int
    {
        if ($staffId === null) {
            return 0;
        }

        return PaymentRequest::where('staff_id', $staffId)->where('status', $status)->count();
    }

    private function countMyRetirements(int|null $staffId, string $status): int
    {
        if ($staffId === null) {
            return 0;
        }

        return RetirementRequest::whereHas(
            'paymentRequest',
            fn($q) => $q->where('staff_id', $staffId),
        )->where('status', $status)->count();
    }

    /** @return list<array{id: int, name: string, balance: float, threshold: float, currency: string}> */
    private function getLowCashBranches(\App\Models\Tenant\User $user): array
    {
        if (! $user->can(PermissionKey::AccessSettings->value)) {
            return [];
        }

        /** @var list<array{id: int, name: string, balance: float, threshold: float, currency: string}> $branches */
        $branches = array_values($this->branchRepository->allWithCashbook()
            ->filter(static function (Branch $branch): bool {
                $cashbook = $branch->cashbook;
                $threshold = $branch->cashBalanceThreshold;

                if (! $cashbook || ! $threshold) {
                    return false;
                }

                return (float) $cashbook->balance < (float) $threshold->threshold_amount;
            })
            ->map(static function (Branch $b): array {
                $cashbook = $b->cashbook;
                $threshold = $b->cashBalanceThreshold;
                assert($cashbook !== null);
                assert($threshold !== null);
                $code = $b->currency->getAttribute('code');

                return [
                    'id' => $b->id,
                    'name' => $b->name,
                    'balance' => (float) $cashbook->balance,
                    'threshold' => (float) $threshold->threshold_amount,
                    'currency' => is_string($code) ? $code : '',
                ];
            })
            ->values()
            ->all());

        return $branches;
    }
}
