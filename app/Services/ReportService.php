<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\PaymentMethod;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\BranchRepository;
use App\Repositories\CashbookRepository;
use App\Repositories\CashCountRepository;
use App\Repositories\PaymentRequestRepository;
use App\Repositories\RetirementRequestRepository;
use App\Repositories\WorkflowActionRepository;
use App\Repositories\WorkflowInstanceRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private readonly PaymentRequestRepository $paymentRequests,
        private readonly RetirementRequestRepository $retirementRequests,
        private readonly CashbookRepository $cashbooks,
        private readonly CashCountRepository $cashCounts,
        private readonly WorkflowInstanceRepository $workflowInstances,
        private readonly WorkflowActionRepository $workflowActions,
        private readonly BranchRepository $branches,
    ) {}

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $groupBy
     * @param string|null $type
     *
     * @return array{
     *     rows: Collection<int, \stdClass>,
     *     grandTotal: float,
     *     dateFrom: string,
     *     dateTo: string,
     *     groupBy: string,
     *     type: string|null,
     * }
     */
    public function expenditureSummary(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        string $groupBy,
        string|null $type,
    ): array {
        $rows = $this->paymentRequests->expenditureSummaryRows($allowedBranchIds, $dateFrom, $dateTo, $type, $groupBy);

        /** @var float|int $grandTotal */
        $grandTotal = $rows->sum('total');

        return [
            'rows' => $rows,
            'grandTotal' => (float) $grandTotal,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'groupBy' => $groupBy,
            'type' => $type,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int|string|null $branchId
     *
     * @return array{
     *     advances: Collection<int, array{request: PaymentRequest, days: int, bucket: string}>,
     *     buckets: Collection<int|string, mixed>,
     *     branches: Collection<int, string>,
     *     branchId: int|string|null,
     * }
     */
    public function outstandingAdvances(array $allowedBranchIds, int|string|null $branchId): array
    {
        /** @var Collection<int, array{request: PaymentRequest, days: int, bucket: string}> $advances */
        $advances = $this->paymentRequests->outstandingAdvances($allowedBranchIds, $branchId)->map(function (PaymentRequest $request): array {
            $days = $request->disbursed_at ? (int) $request->disbursed_at->diffInDays(now()) : 0;
            $bucket = match (true) {
                $days <= 30 => '0–30 days',
                $days <= 60 => '31–60 days',
                default => '61+ days',
            };

            return [
                'request' => $request,
                'days' => $days,
                'bucket' => $bucket,
            ];
        });

        /** @var Collection<int|string, mixed> $buckets */
        $buckets = $advances->groupBy('bucket');

        return [
            'advances' => $advances,
            'buckets' => $buckets,
            'branches' => $this->branches->allByIdsOrderedByName($allowedBranchIds),
            'branchId' => $branchId,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array{
     *     cashbooks: Collection<int, array{cashbook: Cashbook, current_balance: float, period_debits: float, period_credits: float, entry_count: int<0, max>}>,
     *     dateFrom: string,
     *     dateTo: string,
     * }
     */
    public function cashPosition(array $allowedBranchIds, string $dateFrom, string $dateTo): array
    {
        $cashbooks = $this->cashbooks->cashbooksForPosition($allowedBranchIds, $dateFrom, $dateTo)->map(function (Cashbook $book): array {
            $entries = $book->entries;

            /** @var float|int $balance */
            $balance = $book->balance;
            /** @var float|int $debits */
            $debits = $entries->where('type', 'debit')->sum('amount');
            /** @var float|int $credits */
            $credits = $entries->where('type', 'credit')->sum('amount');

            return [
                'cashbook' => $book,
                'current_balance' => (float) $balance,
                'period_debits' => (float) $debits,
                'period_credits' => (float) $credits,
                'entry_count' => $entries->count(),
            ];
        });

        return [
            'cashbooks' => $cashbooks,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     * @param string|null $method
     * @param int $perPage
     *
     * @return array{
     *     disbursements: LengthAwarePaginator<int, PaymentRequest>,
     *     dateFrom: string,
     *     dateTo: string,
     *     branches: Collection<int, string>,
     *     branchId: int|string|null,
     *     method: string|null,
     *     methods: array<int, PaymentMethod>,
     * }
     */
    public function disbursementRegister(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
        string|null $method,
        int $perPage = 50,
    ): array {
        return [
            'disbursements' => $this->paymentRequests->disbursementRegister($allowedBranchIds, $dateFrom, $dateTo, $branchId, $method, $perPage),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branches' => $this->branches->allByIdsOrderedByName($allowedBranchIds),
            'branchId' => $branchId,
            'method' => $method,
            'methods' => PaymentMethod::cases(),
        ];
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array{
     *     stages: Collection<int, array{stage_name: string, count: int, approved: int, sent_back: int, avg_hours: float, min_hours: float, max_hours: float}>,
     *     dateFrom: string,
     *     dateTo: string,
     * }
     */
    public function approvalTurnaround(string $dateFrom, string $dateTo): array
    {
        $groupedStages = $this->workflowInstances->approvalTurnaroundStages($dateFrom, $dateTo)
            ->groupBy('workflow_stage_id');

        /** @var Collection<int, Collection<int, WorkflowInstanceStage>> $groupedStages */
        $groupedStages = $groupedStages;

        $stages = $groupedStages->map(function (Collection $group): array {
            $hours = $group->map(fn(WorkflowInstanceStage $stage) => $stage->started_at->diffInMinutes($stage->completed_at) / 60);
            $approved = $group->where('status', 'approved')->count();
            $sentBack = $group->where('status', 'sent_back')->count();
            $firstStage = $group->first();
            $stageName = 'Unknown';

            if ($firstStage !== null && $firstStage->stage !== null) {
                $stageName = $firstStage->stage->name;
            }

            return [
                'stage_name' => $stageName,
                'count' => $group->count(),
                'approved' => $approved,
                'sent_back' => $sentBack,
                'avg_hours' => (float) round($hours->avg() ?? 0, 1),
                'min_hours' => (float) round($hours->min() ?? 0, 1),
                'max_hours' => (float) round($hours->max() ?? 0, 1),
            ];
        })
            ->sortByDesc('avg_hours')
            ->values();

        return [
            'stages' => $stages,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     *
     * @return array{
     *     activeStages: Collection<int, array{stage: WorkflowInstanceStage, days: int, bucket: string}>,
     *     bucketCounts: Collection<int|string, int>,
     * }
     */
    public function pendingRequestsAging(array $allowedBranchIds): array
    {
        $activeStages = $this->workflowInstances->activeRequestStages()
            ->filter(fn(WorkflowInstanceStage $stage) => in_array(
                $stage->instance?->workflowable?->getAttribute('branch_id'),
                $allowedBranchIds,
                true,
            ))
            ->map(function (WorkflowInstanceStage $stage): array {
                $days = (int) $stage->started_at->diffInDays(now());
                $bucket = match (true) {
                    $days <= 3 => '0–3 days',
                    $days <= 7 => '4–7 days',
                    $days <= 14 => '8–14 days',
                    default => '15+ days',
                };

                return [
                    'stage' => $stage,
                    'days' => $days,
                    'bucket' => $bucket,
                ];
            });

        /** @var Collection<int, array{stage: WorkflowInstanceStage, days: int, bucket: string}> $activeStages */
        $activeStages = $activeStages;
        /** @var Collection<int|string, int> $bucketCounts */
        $bucketCounts = $activeStages->countBy('bucket');

        return [
            'activeStages' => $activeStages,
            'bucketCounts' => $bucketCounts,
        ];
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array{
     *     rows: Collection<int, array{user: User|null, total_actions: int, sent_back_count: int, rate: float}>,
     *     dateFrom: string,
     *     dateTo: string,
     * }
     */
    public function sendBackRate(string $dateFrom, string $dateTo): array
    {
        /** @var EloquentCollection<int, WorkflowAction> $totalByUser */
        $totalByUser = $this->workflowActions->workflowActionTotals($dateFrom, $dateTo);
        /** @var EloquentCollection<int, WorkflowAction> $sentBackByUser */
        $sentBackByUser = $this->workflowActions->workflowActionSentBackTotals($dateFrom, $dateTo);

        $rows = $totalByUser->map(function (WorkflowAction $row) use ($sentBackByUser): array {
            $totalActionsRaw = $row->getAttribute('total_actions');
            $totalActions = is_numeric($totalActionsRaw) ? (int) $totalActionsRaw : 0;
            $sentBackRaw = $sentBackByUser->get($row->user_id)?->getAttribute('sent_back_count') ?? 0;
            $sentBack = is_numeric($sentBackRaw) ? (int) $sentBackRaw : 0;
            $rate = $totalActions > 0 ? (float) round(($sentBack / $totalActions) * 100, 1) : 0.0;

            return [
                'user' => $row->user,
                'total_actions' => $totalActions,
                'sent_back_count' => $sentBack,
                'rate' => $rate,
            ];
        })->sortByDesc('sent_back_count')->values();

        return [
            'rows' => $rows,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $action
     * @param int $perPage
     *
     * @return array{
     *     actions: LengthAwarePaginator<int, WorkflowAction>,
     *     dateFrom: string,
     *     dateTo: string,
     *     action: string|null,
     *     actionTypes: array<int, string>,
     * }
     */
    public function auditTrail(string $dateFrom, string $dateTo, string|null $action, int $perPage = 50): array
    {
        return [
            'actions' => $this->workflowActions->auditTrail($dateFrom, $dateTo, $action, $perPage),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'action' => $action,
            'actionTypes' => ['approved', 'sent_back', 'cancelled'],
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array{
     *     paymentStatuses: EloquentCollection<int, PaymentRequest>,
     *     retirementStatuses: EloquentCollection<int, RetirementRequest>,
     *     paymentTotal: float|int,
     *     retirementTotal: float|int,
     *     dateFrom: string,
     *     dateTo: string,
     * }
     */
    public function requestsByStatus(array $allowedBranchIds, string $dateFrom, string $dateTo): array
    {
        $paymentStatuses = $this->paymentRequests->paymentStatuses($allowedBranchIds, $dateFrom, $dateTo);
        $retirementStatuses = $this->retirementRequests->retirementStatuses($allowedBranchIds, $dateFrom, $dateTo);

        return [
            'paymentStatuses' => $paymentStatuses,
            'retirementStatuses' => $retirementStatuses,
            'paymentTotal' => $paymentStatuses->sum('count'),
            'retirementTotal' => $retirementStatuses->sum('count'),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     *
     * @return array{
     *     rows: Collection<int, \stdClass>,
     *     totalDisbursed: float,
     *     totalExpended: float,
     *     totalDifference: float,
     *     dateFrom: string,
     *     dateTo: string,
     *     branches: Collection<int, string>,
     *     branchId: int|string|null,
     * }
     */
    public function retirementVariance(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
    ): array {
        $rows = $this->retirementRequests->varianceRows($allowedBranchIds, $dateFrom, $dateTo, $branchId);

        /** @var float|int $totalDisbursed */
        $totalDisbursed = $rows->sum('disbursed_amount');
        /** @var float|int $totalExpended */
        $totalExpended = $rows->sum('total_amount_expended');
        /** @var float|int $totalDifference */
        $totalDifference = $rows->sum('difference_amount');

        return [
            'rows' => $rows,
            'totalDisbursed' => (float) $totalDisbursed,
            'totalExpended' => (float) $totalExpended,
            'totalDifference' => (float) $totalDifference,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branches' => $this->branches->allByIdsOrderedByName($allowedBranchIds),
            'branchId' => $branchId,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     * @param string|null $type
     *
     * @return array{
     *     rows: Collection<int, \stdClass>,
     *     byBranch: Collection<int|string, mixed>,
     *     deniedCount: int,
     *     cancelledCount: int,
     *     dateFrom: string,
     *     dateTo: string,
     *     branches: Collection<int, string>,
     *     branchId: int|string|null,
     *     type: string|null,
     * }
     */
    public function deniedCancelledAnalysis(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
        string|null $type,
    ): array {
        $rows = $this->paymentRequests->deniedCancelledRows($allowedBranchIds, $dateFrom, $dateTo, $branchId, $type);

        /** @var Collection<int|string, mixed> $byBranch */
        $byBranch = $rows->groupBy('branch_name');
        /** @var int $deniedCount */
        $deniedCount = (int) $rows->where('status', 'denied')->sum('count');
        /** @var int $cancelledCount */
        $cancelledCount = (int) $rows->where('status', 'cancelled')->sum('count');

        return [
            'rows' => $rows,
            'byBranch' => $byBranch,
            'deniedCount' => $deniedCount,
            'cancelledCount' => $cancelledCount,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branches' => $this->branches->allByIdsOrderedByName($allowedBranchIds),
            'branchId' => $branchId,
            'type' => $type,
        ];
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array{
     *     stages: Collection<int, array{stage_name: string, count: int, approved: int, sent_back: int, avg_hours: float, min_hours: float, max_hours: float}>,
     *     dateFrom: string,
     *     dateTo: string,
     * }
     */
    public function retirementTurnaround(string $dateFrom, string $dateTo): array
    {
        $groupedStages = $this->workflowInstances->retirementTurnaroundStages($dateFrom, $dateTo)
            ->groupBy('workflow_stage_id');

        /** @var Collection<int, Collection<int, WorkflowInstanceStage>> $groupedStages */
        $groupedStages = $groupedStages;

        $stages = $groupedStages->map(function (Collection $group): array {
            $hours = $group->map(fn(WorkflowInstanceStage $stage) => $stage->started_at->diffInMinutes($stage->completed_at) / 60);
            $approved = $group->where('status', 'approved')->count();
            $sentBack = $group->where('status', 'sent_back')->count();
            $firstStage = $group->first();
            $stageName = 'Unknown';

            if ($firstStage !== null && $firstStage->stage !== null) {
                $stageName = $firstStage->stage->name;
            }

            return [
                'stage_name' => $stageName,
                'count' => $group->count(),
                'approved' => $approved,
                'sent_back' => $sentBack,
                'avg_hours' => (float) round($hours->avg() ?? 0, 1),
                'min_hours' => (float) round($hours->min() ?? 0, 1),
                'max_hours' => (float) round($hours->max() ?? 0, 1),
            ];
        })
            ->sortByDesc('avg_hours')
            ->values();

        return [
            'stages' => $stages,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $slaDays
     * @param string $dateFrom
     * @param string $dateTo
     * @param string|null $type
     *
     * @return array{
     *     requests: Collection<int, array{request: PaymentRequest, days: int, compliant: bool}>,
     *     compliantCount: int,
     *     total: int,
     *     complianceRate: float,
     *     avgDays: float,
     *     slaDays: int,
     *     dateFrom: string,
     *     dateTo: string,
     *     type: string|null,
     * }
     */
    public function workflowSla(
        array $allowedBranchIds,
        int $slaDays,
        string $dateFrom,
        string $dateTo,
        string|null $type,
    ): array {
        $requests = $this->paymentRequests->workflowSlaRequests($allowedBranchIds, $dateFrom, $dateTo, $type)->map(function (PaymentRequest $request) use ($slaDays): array {
            $days = $request->submitted_at && $request->approved_at
                ? (int) $request->submitted_at->diffInDays($request->approved_at)
                : 0;

            return [
                'request' => $request,
                'days' => $days,
                'compliant' => $days <= $slaDays,
            ];
        });

        /** @var int $compliantCount */
        $compliantCount = (int) $requests->where('compliant', true)->count();
        /** @var int $total */
        $total = (int) $requests->count();
        $complianceRate = $total > 0 ? (float) round(($compliantCount / $total) * 100, 1) : 0.0;
        $avgDays = $total > 0 ? (float) round((float) $requests->avg('days'), 1) : 0.0;

        return [
            'requests' => $requests,
            'compliantCount' => $compliantCount,
            'total' => $total,
            'complianceRate' => $complianceRate,
            'avgDays' => $avgDays,
            'slaDays' => $slaDays,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'type' => $type,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $year
     * @param string|null $type
     *
     * @return array{
     *     rows: EloquentCollection<int, PaymentRequest>,
     *     year: int,
     *     years: Collection<int, int>,
     *     type: string|null,
     * }
     */
    public function spendTrend(array $allowedBranchIds, int $year, string|null $type): array
    {
        $rows = $this->paymentRequests->spendTrendRows($allowedBranchIds, $year, $type);
        $years = $this->paymentRequests->spendTrendYears($allowedBranchIds);

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return [
            'rows' => $rows,
            'year' => $year,
            'years' => $years,
            'type' => $type,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $groupBy
     * @param string|null $type
     *
     * @return array{
     *     rows: Collection<int, \stdClass>,
     *     dateFrom: string,
     *     dateTo: string,
     *     groupBy: string,
     *     type: string|null,
     * }
     */
    public function topSpenders(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        string $groupBy,
        string|null $type,
    ): array {
        return [
            'rows' => $this->paymentRequests->topSpendersRows($allowedBranchIds, $dateFrom, $dateTo, $groupBy, $type),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'groupBy' => $groupBy,
            'type' => $type,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param array<int, string> $statuses
     * @param string $dateField
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     * @param int|string|null $staffId
     * @param int|string|null $departmentId
     * @param int|string|null $costCodeId
     * @param string|null $type
     * @param string $title
     * @param int $perPage
     *
     * @return array{
     *     rows: LengthAwarePaginator<int, PaymentRequest>,
     *     title: string,
     *     dateFrom: string,
     *     dateTo: string,
     * }
     */
    public function paymentRequestBreakdown(
        array $allowedBranchIds,
        array $statuses,
        string $dateField,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
        int|string|null $staffId,
        int|string|null $departmentId,
        int|string|null $costCodeId,
        string|null $type,
        string $title,
        int $perPage = 50,
    ): array {
        return [
            'rows' => $this->paymentRequests->breakdown(
                $allowedBranchIds,
                $statuses,
                $dateField,
                $dateFrom,
                $dateTo,
                $branchId,
                $staffId,
                $departmentId,
                $costCodeId,
                $type,
                $perPage,
            ),
            'title' => $title,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     *
     * @return array{
     *     rows: EloquentCollection<int, CashCount>,
     *     totalCounts: int,
     *     withDiscrepancy: int,
     *     netDifference: float,
     *     dateFrom: string,
     *     dateTo: string,
     *     branches: Collection<int, string>,
     *     branchId: int|string|null,
     * }
     */
    public function cashCountReport(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
    ): array {
        $rows = $this->cashCounts->forReport($allowedBranchIds, $dateFrom, $dateTo, $branchId);

        $withDiscrepancy = $rows->filter(fn($row) => abs((float) $row->difference) > 0.01)->count();
        $netDifference = $rows->sum(fn($row) => (float) $row->difference);

        return [
            'rows' => $rows,
            'totalCounts' => $rows->count(),
            'withDiscrepancy' => $withDiscrepancy,
            'netDifference' => round($netDifference, 2),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'branches' => $this->branches->allByIdsOrderedByName($allowedBranchIds),
            'branchId' => $branchId,
        ];
    }
}
