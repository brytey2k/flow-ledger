<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Repositories\BranchRepository;
use App\Repositories\PaymentRequestRepository;
use App\Repositories\RetirementRequestRepository;
use App\Repositories\WorkflowInstanceRepository;

class DashboardAnalyticsService
{
    public function __construct(
        private readonly BranchScopeService $branchScopeService,
        private readonly BranchRepository $branchRepository,
        private readonly PaymentRequestRepository $paymentRequestRepository,
        private readonly RetirementRequestRepository $retirementRequestRepository,
        private readonly WorkflowInstanceRepository $workflowInstanceRepository,
        private readonly DashboardCacheService $dashboardCacheService,
    ) {}

    /**
     * @param User $user
     *
     * @return array{
     *     generated_at: string,
     *     permissions: array{
     *         can_access_reports: bool,
     *         can_access_settings: bool,
     *     },
     *     summary: array{
     *         pending_approvals: int,
     *         pending_disbursements: int,
     *         overdue_advances: int,
     *         low_cash_branches: int,
     *         requests_created_30d: int,
     *         disbursed_total_30d: float,
     *         send_back_rate_30d: float,
     *     },
     *     personal: array{
     *         my_draft_requests: int,
     *         my_in_workflow_requests: int,
     *         my_draft_retirements: int,
     *     },
     *     pipeline: array{
     *         payment_request_statuses: array<int, array{status: string, count: int, total: float}>,
     *         retirement_statuses: array<int, array{status: string, count: int, total: float}>,
     *         approval_aging_buckets: array<int, array{bucket: string, count: int}>,
     *     },
     *     trends: array{
     *         monthly_spend: array<int, array{month: string, month_label: string, total: float, count: int}>,
     *     },
     *     insights: array{
     *         top_spending_branches_30d: array<int, array{branch_id: int, branch_name: string, total: float, count: int}>,
     *         overdue_advances_by_branch: array<int, array{branch_id: int, branch_name: string, overdue_count: int, overdue_total: float}>,
     *         low_cash_branches: array<int, array{id: int, name: string, balance: float, threshold: float, currency_symbol: string, currency_code: string}>,
     *     },
     *     links: array<string, string>,
     * }
     */
    public function payloadForUser(User $user): array
    {
        $allowedBranchIds = $this->branchScopeService->allowedBranchIds($user);
        $staffId = $user->staffProfile?->id;
        $tenantKey = $this->dashboardCacheService->resolveTenantCacheKey();

        return $this->dashboardCacheService->rememberUserPayload(
            $tenantKey,
            $user->id,
            $allowedBranchIds,
            function () use ($user, $allowedBranchIds, $staffId): array {
                $canAccessReports = $user->can(PermissionKey::AccessReports->value);
                $canAccessSettings = $user->can(PermissionKey::AccessSettings->value);

                $lowCashBranches = $canAccessSettings
                    ? $this->branchRepository->lowCashBranchAlerts($allowedBranchIds)->all()
                    : [];

                $pendingApprovals = $this->workflowInstanceRepository->pendingApprovalsCountForUser($user);
                $pendingDisbursements = $this->paymentRequestRepository->countPendingDisbursements($allowedBranchIds, $user);
                $myDraftRequests = $this->paymentRequestRepository->countByStaffAndStatus($staffId, 'draft');
                $myInWorkflowRequests = $this->paymentRequestRepository->countByStaffAndStatus($staffId, 'in_workflow');
                $myDraftRetirements = $this->retirementRequestRepository->countByStaffAndStatus($staffId, 'draft');

                return [
                    'generated_at' => now()->toIso8601String(),
                    'permissions' => [
                        'can_access_reports' => $canAccessReports,
                        'can_access_settings' => $canAccessSettings,
                    ],
                    'summary' => [
                        'pending_approvals' => $pendingApprovals,
                        'pending_disbursements' => $pendingDisbursements,
                        'overdue_advances' => $this->paymentRequestRepository->countOverdueOutstandingAdvances($allowedBranchIds, 30),
                        'low_cash_branches' => count($lowCashBranches),
                        'requests_created_30d' => $this->paymentRequestRepository->countCreatedInLastDays($allowedBranchIds, 30),
                        'disbursed_total_30d' => $this->paymentRequestRepository->sumDisbursedInLastDays($allowedBranchIds, 30),
                        'send_back_rate_30d' => $this->workflowInstanceRepository->sendBackRateInLastDays($allowedBranchIds, 30),
                    ],
                    'personal' => [
                        'my_draft_requests' => $myDraftRequests,
                        'my_in_workflow_requests' => $myInWorkflowRequests,
                        'my_draft_retirements' => $myDraftRetirements,
                    ],
                    'pipeline' => [
                        'payment_request_statuses' => $this->paymentRequestRepository
                            ->paymentStatuses($allowedBranchIds)
                            ->map(static function (PaymentRequest $row): array {
                                $count = $row->getAttribute('count');
                                $total = $row->getAttribute('total');

                                return [
                                    'status' => (string) $row->status,
                                    'count' => is_numeric($count) ? (int) $count : 0,
                                    'total' => is_numeric($total) ? (float) $total : 0.0,
                                ];
                            })
                            ->values()
                            ->all(),
                        'retirement_statuses' => $this->retirementRequestRepository
                            ->retirementStatuses($allowedBranchIds)
                            ->map(static function (RetirementRequest $row): array {
                                $count = $row->getAttribute('count');
                                $total = $row->getAttribute('total');

                                return [
                                    'status' => (string) $row->status,
                                    'count' => is_numeric($count) ? (int) $count : 0,
                                    'total' => is_numeric($total) ? (float) $total : 0.0,
                                ];
                            })
                            ->values()
                            ->all(),
                        'approval_aging_buckets' => $this->workflowInstanceRepository->approvalAgingBuckets($allowedBranchIds)->all(),
                    ],
                    'trends' => [
                        'monthly_spend' => $this->paymentRequestRepository->monthlySpendTrend($allowedBranchIds, 6)->all(),
                    ],
                    'insights' => [
                        'top_spending_branches_30d' => $this->paymentRequestRepository->topSpendingBranchesInLastDays($allowedBranchIds, 30, 5)->all(),
                        'overdue_advances_by_branch' => $this->paymentRequestRepository->overdueOutstandingAdvancesByBranch($allowedBranchIds, 30, 5)->all(),
                        'low_cash_branches' => $lowCashBranches,
                    ],
                    'links' => [
                        'approvals' => route('approvals.index'),
                        'disbursements' => route('disbursements.index'),
                        'payment_requests' => route('payment-requests.index'),
                        'retirement_requests' => route('retirement-requests.index'),
                        'reports' => route('reports.index'),
                        'cash_thresholds' => route('cash-balance-thresholds.index'),
                    ],
                ];
            },
        );
    }
}
