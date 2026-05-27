<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\Cashbook;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowInstanceStage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportRepository
{
    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param ?string $type
     * @param string $groupBy
     *
     * @return Collection<int, \stdClass>
     */
    public function expenditureSummaryRows(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        string|null $type,
        string $groupBy,
    ): Collection {
        $base = DB::table('payment_requests')
            ->where('payment_requests.status', 'disbursed')
            ->whereNull('payment_requests.deleted_at')
            ->whereIn('payment_requests.branch_id', $allowedBranchIds)
            ->whereBetween('payment_requests.disbursed_at', [$dateFrom, $dateTo])
            ->when($type, fn(QueryBuilder $query) => $query->where('payment_requests.type', $type));

        if ($groupBy === 'branch') {
            return (clone $base)
                ->join('branches', 'payment_requests.branch_id', '=', 'branches.id')
                ->whereNull('branches.deleted_at')
                ->selectRaw('branches.name as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total')
                ->groupBy('branches.id', 'branches.name')
                ->orderByDesc('total')
                ->get();
        }

        if ($groupBy === 'cost_code') {
            return DB::table('payment_request_items')
                ->join('payment_requests', 'payment_request_items.payment_request_id', '=', 'payment_requests.id')
                ->join('cost_codes', 'payment_request_items.cost_code_id', '=', 'cost_codes.id')
                ->where('payment_requests.status', 'disbursed')
                ->whereNull('payment_requests.deleted_at')
                ->whereIn('payment_requests.branch_id', $allowedBranchIds)
                ->whereBetween('payment_requests.disbursed_at', [$dateFrom, $dateTo])
                ->when($type, fn(QueryBuilder $query) => $query->where('payment_requests.type', $type))
                ->selectRaw('CONCAT(cost_codes.code, \' - \', cost_codes.name) as label, COUNT(DISTINCT payment_requests.id) as count, SUM(payment_request_items.amount) as total')
                ->groupBy('cost_codes.id', 'cost_codes.code', 'cost_codes.name')
                ->orderByDesc('total')
                ->get();
        }

        return (clone $base)
            ->join('staff', 'payment_requests.staff_id', '=', 'staff.id')
            ->join('departments', 'staff.department_id', '=', 'departments.id')
            ->whereNull('staff.deleted_at')
            ->selectRaw('departments.name as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total')
            ->groupBy('departments.id', 'departments.name')
            ->orderByDesc('total')
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int|string|null $branchId
     *
     * @return EloquentCollection<int, PaymentRequest>
     */
    public function outstandingAdvances(array $allowedBranchIds, int|string|null $branchId): EloquentCollection
    {
        return PaymentRequest::query()
            ->with(['staff.department', 'branch', 'currency', 'retirementRequests'])
            ->where('type', 'advance')
            ->where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->when($branchId, fn(EloquentBuilder $query) => $query->where('branch_id', $branchId))
            ->whereDoesntHave('retirementRequests', fn(EloquentBuilder $query) => $query->whereIn('status', ['approved', 'settled']))
            ->orderBy('disbursed_at')
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return EloquentCollection<int, Cashbook>
     */
    public function cashbooksForPosition(array $allowedBranchIds, string $dateFrom, string $dateTo): EloquentCollection
    {
        return Cashbook::with([
            'branch',
            'currency',
            'entries' => fn(HasMany $query) => $query
                ->whereBetween('entry_date', [$dateFrom, $dateTo])
                ->whereNull('deleted_at'),
        ])
            ->whereIn('branch_id', $allowedBranchIds)
            ->withCount('entries')
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     * @param ?string $method
     *
     * @return LengthAwarePaginator<int, PaymentRequest>
     */
    public function disbursementRegister(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
        string|null $method,
    ): LengthAwarePaginator {
        return PaymentRequest::with(['staff', 'branch', 'currency', 'disbursedBy'])
            ->where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereBetween('disbursed_at', [$dateFrom, $dateTo])
            ->when($branchId, fn(EloquentBuilder $query) => $query->where('branch_id', $branchId))
            ->when($method, fn(EloquentBuilder $query) => $query->where('disbursement_method', $method))
            ->orderByDesc('disbursed_at')
            ->paginate(50)
            ->withQueryString();
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
     * @return EloquentCollection<int, WorkflowInstanceStage>
     */
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

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return EloquentCollection<int, WorkflowAction>
     */
    public function workflowActionTotals(string $dateFrom, string $dateTo): EloquentCollection
    {
        return WorkflowAction::with('user')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('user_id', DB::raw('COUNT(*) as total_actions'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return EloquentCollection<int, WorkflowAction>
     */
    public function workflowActionSentBackTotals(string $dateFrom, string $dateTo): EloquentCollection
    {
        return WorkflowAction::with('user')
            ->where('action', 'sent_back')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('user_id', DB::raw('COUNT(*) as sent_back_count'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param ?string $action
     *
     * @return LengthAwarePaginator<int, WorkflowAction>
     */
    public function auditTrail(string $dateFrom, string $dateTo, string|null $action): LengthAwarePaginator
    {
        return WorkflowAction::with([
            'user',
            'instanceStage.stage',
            'instanceStage.instance.workflowable',
        ])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($action, fn($query) => $query->where('action', $action))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     *
     * @return EloquentCollection<int, PaymentRequest>
     */
    public function paymentStatuses(array $allowedBranchIds): EloquentCollection
    {
        return PaymentRequest::whereIn('branch_id', $allowedBranchIds)
            ->select('status', DB::raw('COUNT(*) as count, SUM(total_amount) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     *
     * @return EloquentCollection<int, RetirementRequest>
     */
    public function retirementStatuses(array $allowedBranchIds): EloquentCollection
    {
        return RetirementRequest::whereHas('paymentRequest', fn(EloquentBuilder $query) => $query->whereIn('branch_id', $allowedBranchIds))
            ->select('status', DB::raw('COUNT(*) as count, SUM(total_amount_expended) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param ?string $type
     *
     * @return EloquentCollection<int, PaymentRequest>
     */
    public function workflowSlaRequests(array $allowedBranchIds, string $dateFrom, string $dateTo, string|null $type): EloquentCollection
    {
        return PaymentRequest::whereNotNull('approved_at')
            ->whereNotNull('submitted_at')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereBetween('approved_at', [$dateFrom, $dateTo])
            ->when($type, fn(EloquentBuilder $query) => $query->where('type', $type))
            ->with(['staff', 'branch', 'currency'])
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $year
     * @param ?string $type
     *
     * @return EloquentCollection<int, PaymentRequest>
     */
    public function spendTrendRows(array $allowedBranchIds, int $year, string|null $type): EloquentCollection
    {
        return PaymentRequest::where('status', 'disbursed')
            ->whereNotNull('disbursed_at')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereYear('disbursed_at', $year)
            ->when($type, fn(EloquentBuilder $query) => $query->where('type', $type))
            ->selectRaw("TO_CHAR(disbursed_at, 'Mon') as month_label, EXTRACT(MONTH FROM disbursed_at) as month_num, SUM(total_amount) as total, COUNT(*) as count")
            ->groupByRaw("TO_CHAR(disbursed_at, 'Mon'), EXTRACT(MONTH FROM disbursed_at)")
            ->orderByRaw('EXTRACT(MONTH FROM disbursed_at)')
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     *
     * @return Collection<int, int>
     */
    public function spendTrendYears(array $allowedBranchIds): Collection
    {
        return PaymentRequest::where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereNotNull('disbursed_at')
            ->selectRaw('EXTRACT(YEAR FROM disbursed_at) as yr')
            ->groupByRaw('EXTRACT(YEAR FROM disbursed_at)')
            ->orderByRaw('EXTRACT(YEAR FROM disbursed_at) DESC')
            ->pluck('yr')
            ->map(fn($year) => is_numeric($year) ? (int) $year : 0);
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param string $groupBy
     * @param ?string $type
     *
     * @return Collection<int, \stdClass>
     */
    public function topSpendersRows(array $allowedBranchIds, string $dateFrom, string $dateTo, string $groupBy, string|null $type): Collection
    {
        if ($groupBy === 'department') {
            return DB::table('payment_requests')
                ->join('staff', 'payment_requests.staff_id', '=', 'staff.id')
                ->join('departments', 'staff.department_id', '=', 'departments.id')
                ->where('payment_requests.status', 'disbursed')
                ->whereNull('payment_requests.deleted_at')
                ->whereNull('staff.deleted_at')
                ->whereIn('payment_requests.branch_id', $allowedBranchIds)
                ->whereBetween('payment_requests.disbursed_at', [$dateFrom, $dateTo])
                ->when($type, fn(QueryBuilder $query) => $query->where('payment_requests.type', $type))
                ->selectRaw('departments.name as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total')
                ->groupBy('departments.id', 'departments.name')
                ->orderByDesc('total')
                ->limit(20)
                ->get();
        }

        return DB::table('payment_requests')
            ->join('staff', 'payment_requests.staff_id', '=', 'staff.id')
            ->where('payment_requests.status', 'disbursed')
            ->whereNull('payment_requests.deleted_at')
            ->whereNull('staff.deleted_at')
            ->whereIn('payment_requests.branch_id', $allowedBranchIds)
            ->whereBetween('payment_requests.disbursed_at', [$dateFrom, $dateTo])
            ->when($type, fn($query) => $query->where('payment_requests.type', $type))
            ->selectRaw("CONCAT(staff.first_name, ' ', staff.last_name) as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total")
            ->groupBy('staff.id', 'staff.first_name', 'staff.last_name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();
    }
}
