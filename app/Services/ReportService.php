<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\PaymentMethod;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\BranchRepository;
use App\Repositories\ReportRepository;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class ReportService
{
    public function __construct(
        private readonly ReportRepository $reports,
        private readonly BranchRepository $branches,
    ) {}

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $groupBy
     * @param ?string $type
     *
     * @return array<string, mixed>
     */
    public function expenditureSummary(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        string $groupBy,
        string|null $type,
    ): array {
        $rows = $this->reports->expenditureSummaryRows($allowedBranchIds, $dateFrom, $dateTo, $type, $groupBy);

        return [
            'rows' => $rows,
            'grandTotal' => $rows->sum('total'),
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
     * @return array<string, mixed>
     */
    public function outstandingAdvances(array $allowedBranchIds, int|string|null $branchId): array
    {
        $advances = $this->reports->outstandingAdvances($allowedBranchIds, $branchId)->map(function (PaymentRequest $request): array {
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

        return [
            'advances' => $advances,
            'buckets' => $advances->groupBy('bucket'),
            'branches' => $this->branches->allByIdsOrderedByName($allowedBranchIds),
            'branchId' => $branchId,
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array<string, mixed>
     */
    public function cashPosition(array $allowedBranchIds, string $dateFrom, string $dateTo): array
    {
        $cashbooks = $this->reports->cashbooksForPosition($allowedBranchIds, $dateFrom, $dateTo)->map(function (Cashbook $book): array {
            $entries = $book->entries;

            return [
                'cashbook' => $book,
                'current_balance' => $book->balance,
                'period_debits' => $entries->where('type', 'debit')->sum('amount'),
                'period_credits' => $entries->where('type', 'credit')->sum('amount'),
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
     * @param ?string $method
     *
     * @return array<string, mixed>
     */
    public function disbursementRegister(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
        string|null $method,
    ): array {
        return [
            'disbursements' => $this->reports->disbursementRegister($allowedBranchIds, $dateFrom, $dateTo, $branchId, $method),
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
     * @return array<string, mixed>
     */
    public function approvalTurnaround(string $dateFrom, string $dateTo): array
    {
        $groupedStages = $this->reports->approvalTurnaroundStages($dateFrom, $dateTo)
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
     * @return array<string, mixed>
     */
    public function pendingRequestsAging(array $allowedBranchIds): array
    {
        $activeStages = $this->reports->activeRequestStages()
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

        return [
            'activeStages' => $activeStages,
            'bucketCounts' => $activeStages->countBy('bucket'),
        ];
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array<string, mixed>
     */
    public function sendBackRate(string $dateFrom, string $dateTo): array
    {
        /** @var EloquentCollection<int, WorkflowAction> $totalByUser */
        $totalByUser = $this->reports->workflowActionTotals($dateFrom, $dateTo);
        /** @var EloquentCollection<int, WorkflowAction> $sentBackByUser */
        $sentBackByUser = $this->reports->workflowActionSentBackTotals($dateFrom, $dateTo);

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
     * @param ?string $action
     *
     * @return array<string, mixed>
     */
    public function auditTrail(string $dateFrom, string $dateTo, string|null $action): array
    {
        return [
            'actions' => $this->reports->auditTrail($dateFrom, $dateTo, $action),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'action' => $action,
            'actionTypes' => ['approved', 'sent_back', 'cancelled'],
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     *
     * @return array<string, mixed>
     */
    public function requestsByStatus(array $allowedBranchIds): array
    {
        $paymentStatuses = $this->reports->paymentStatuses($allowedBranchIds);
        $retirementStatuses = $this->reports->retirementStatuses($allowedBranchIds);

        return [
            'paymentStatuses' => $paymentStatuses,
            'retirementStatuses' => $retirementStatuses,
            'paymentTotal' => $paymentStatuses->sum('count'),
            'retirementTotal' => $retirementStatuses->sum('count'),
        ];
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $slaDays
     * @param string $dateFrom
     * @param string $dateTo
     * @param ?string $type
     *
     * @return array<string, mixed>
     */
    public function workflowSla(
        array $allowedBranchIds,
        int $slaDays,
        string $dateFrom,
        string $dateTo,
        string|null $type,
    ): array {
        $requests = $this->reports->workflowSlaRequests($allowedBranchIds, $dateFrom, $dateTo, $type)->map(function (PaymentRequest $request) use ($slaDays): array {
            $days = $request->submitted_at && $request->approved_at
                ? $request->submitted_at->diffInDays($request->approved_at)
                : 0;

            return [
                'request' => $request,
                'days' => $days,
                'compliant' => $days <= $slaDays,
            ];
        });

        $compliantCount = $requests->where('compliant', true)->count();
        $total = $requests->count();
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
     * @param ?string $type
     *
     * @return array<string, mixed>
     */
    public function spendTrend(array $allowedBranchIds, int $year, string|null $type): array
    {
        $rows = $this->reports->spendTrendRows($allowedBranchIds, $year, $type);
        $years = $this->reports->spendTrendYears($allowedBranchIds);

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
     * @param ?string $type
     *
     * @return array<string, mixed>
     */
    public function topSpenders(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        string $groupBy,
        string|null $type,
    ): array {
        return [
            'rows' => $this->reports->topSpendersRows($allowedBranchIds, $dateFrom, $dateTo, $groupBy, $type),
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'groupBy' => $groupBy,
            'type' => $type,
        ];
    }
}
