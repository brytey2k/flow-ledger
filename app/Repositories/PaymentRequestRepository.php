<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PaymentRequestRepository
{
    public function __construct(private readonly WorkflowApproverRepository $workflowApprovers) {}

    public function countByStaffAndStatus(int|null $staffId, string $status): int
    {
        if ($staffId === null) {
            return 0;
        }

        return PaymentRequest::query()
            ->where('staff_id', $staffId)
            ->where('status', $status)
            ->count();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param User|null $user
     */
    public function countPendingDisbursements(array $allowedBranchIds, User|null $user = null): int
    {
        $query = PaymentRequest::query()
            ->whereIn('branch_id', $allowedBranchIds)
            ->where('status', 'approved');

        if ($user !== null) {
            $this->workflowApprovers->excludePaymentRequestsParticipatedIn($query, $user);
        }

        return $query->count();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $graceDays
     */
    public function countOverdueOutstandingAdvances(array $allowedBranchIds, int $graceDays = 30): int
    {
        return PaymentRequest::query()
            ->where('type', 'advance')
            ->where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereNotNull('disbursed_at')
            ->whereDate('disbursed_at', '<=', now()->subDays($graceDays)->toDateString())
            ->count();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $days
     */
    public function sumDisbursedInLastDays(array $allowedBranchIds, int $days = 30): float
    {
        /** @var float|int|null $sum */
        $sum = PaymentRequest::query()
            ->where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereDate('disbursed_at', '>=', now()->subDays($days)->toDateString())
            ->sum('total_amount');

        return (float) ($sum ?? 0);
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $days
     */
    public function countCreatedInLastDays(array $allowedBranchIds, int $days = 30): int
    {
        return PaymentRequest::query()
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereDate('created_at', '>=', now()->subDays($days)->toDateString())
            ->count();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $months
     *
     * @return Collection<int, array{month: string, month_label: string, total: float, count: int}>
     */
    public function monthlySpendTrend(array $allowedBranchIds, int $months = 6): Collection
    {
        $start = now()->startOfMonth()->subMonths($months - 1)->toDateString();

        /** @var Collection<int, object{month: string, month_label: string, total: float|int|string, count: int|float|string}> $rows */
        $rows = DB::table('payment_requests')
            ->where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereDate('disbursed_at', '>=', $start)
            ->selectRaw("TO_CHAR(disbursed_at, 'YYYY-MM') as month, TO_CHAR(disbursed_at, 'Mon YYYY') as month_label, SUM(total_amount) as total, COUNT(*) as count")
            ->groupByRaw("TO_CHAR(disbursed_at, 'YYYY-MM'), TO_CHAR(disbursed_at, 'Mon YYYY')")
            ->orderByRaw("TO_CHAR(disbursed_at, 'YYYY-MM') ASC")
            ->get();

        /** @var Collection<int, array{month: string, month_label: string, total: float, count: int}> $result */
        $result = $rows->map(static fn(object $row): array => [
            'month' => (string) $row->month,
            'month_label' => (string) $row->month_label,
            'total' => (float) $row->total,
            'count' => (int) $row->count,
        ])->values();

        return $result;
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $days
     * @param int $limit
     *
     * @return Collection<int, array{branch_id: int, branch_name: string, total: float, count: int}>
     */
    public function topSpendingBranchesInLastDays(array $allowedBranchIds, int $days = 30, int $limit = 5): Collection
    {
        /** @var Collection<int, object{branch_id: int, branch_name: string, total: float|int|string, count: int|float|string}> $rows */
        $rows = DB::table('payment_requests')
            ->join('branches', 'branches.id', '=', 'payment_requests.branch_id')
            ->where('payment_requests.status', 'disbursed')
            ->whereIn('payment_requests.branch_id', $allowedBranchIds)
            ->whereDate('payment_requests.disbursed_at', '>=', now()->subDays($days)->toDateString())
            ->whereNull('payment_requests.deleted_at')
            ->whereNull('branches.deleted_at')
            ->selectRaw('branches.id as branch_id, branches.name as branch_name, SUM(payment_requests.total_amount) as total, COUNT(payment_requests.id) as count')
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('total')
            ->limit($limit)
            ->get();

        /** @var Collection<int, array{branch_id: int, branch_name: string, total: float, count: int}> $result */
        $result = $rows->map(static fn(object $row): array => [
            'branch_id' => (int) $row->branch_id,
            'branch_name' => (string) $row->branch_name,
            'total' => (float) $row->total,
            'count' => (int) $row->count,
        ])->values();

        return $result;
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param int $graceDays
     * @param int $limit
     *
     * @return Collection<int, array{branch_id: int, branch_name: string, overdue_count: int, overdue_total: float}>
     */
    public function overdueOutstandingAdvancesByBranch(array $allowedBranchIds, int $graceDays = 30, int $limit = 5): Collection
    {
        /** @var Collection<int, object{branch_id: int, branch_name: string, overdue_count: int|float|string, overdue_total: float|int|string}> $rows */
        $rows = DB::table('payment_requests')
            ->join('branches', 'branches.id', '=', 'payment_requests.branch_id')
            ->where('payment_requests.type', 'advance')
            ->where('payment_requests.status', 'disbursed')
            ->whereIn('payment_requests.branch_id', $allowedBranchIds)
            ->whereNotNull('payment_requests.disbursed_at')
            ->whereDate('payment_requests.disbursed_at', '<=', now()->subDays($graceDays)->toDateString())
            ->whereNull('payment_requests.deleted_at')
            ->whereNull('branches.deleted_at')
            ->selectRaw('branches.id as branch_id, branches.name as branch_name, COUNT(payment_requests.id) as overdue_count, SUM(payment_requests.total_amount) as overdue_total')
            ->groupBy('branches.id', 'branches.name')
            ->orderByDesc('overdue_count')
            ->limit($limit)
            ->get();

        /** @var Collection<int, array{branch_id: int, branch_name: string, overdue_count: int, overdue_total: float}> $result */
        $result = $rows->map(static fn(object $row): array => [
            'branch_id' => (int) $row->branch_id,
            'branch_name' => (string) $row->branch_name,
            'overdue_count' => (int) $row->overdue_count,
            'overdue_total' => (float) $row->overdue_total,
        ])->values();

        return $result;
    }

    /**
     * @param array<int, int> $branchIds
     * @param int $perPage
     * @param string|null $status
     * @param int|null $staffId
     *
     * @return LengthAwarePaginator<int, PaymentRequest>
     */
    public function paginated(array $branchIds, int $perPage = 20, string|null $status = null, int|null $staffId = null): LengthAwarePaginator
    {
        return PaymentRequest::with([
            'staff', 'branch', 'currency',
            'retirementRequests' => fn(HasMany $q) => $q->whereIn('status', ['draft', 'in_workflow', 'approved', 'sent_back']),
        ])
            ->whereIn('branch_id', $branchIds)
            ->when($staffId, fn(EloquentBuilder $q) => $q->where('staff_id', $staffId))
            ->when($status === 'pending_retirement', fn(EloquentBuilder $q) => $q
                ->where('type', 'advance')
                ->where('status', 'disbursed')
                ->whereDoesntHave('retirementRequests', fn(EloquentBuilder $q2) => $q2->whereIn('status', ['draft', 'in_workflow', 'approved', 'sent_back'])))
            ->when($status !== null && $status !== 'pending_retirement', fn(EloquentBuilder $q) => $q->where('status', $status))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array<int, int> $branchIds
     * @param int $perPage
     * @param User|null $user
     *
     * @return LengthAwarePaginator<int, PaymentRequest>
     */
    public function pendingDisbursement(array $branchIds, int $perPage = 20, User|null $user = null): LengthAwarePaginator
    {
        $query = PaymentRequest::with(['staff', 'branch', 'currency'])
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'approved')
            ->orderBy('approved_at', 'asc');

        if ($user !== null) {
            $this->workflowApprovers->excludePaymentRequestsParticipatedIn($query, $user);
        }

        return $query->paginate($perPage);
    }

    public function findWithDetails(int|string $id): PaymentRequest
    {
        return PaymentRequest::with([
            'staff',
            'branch',
            'currency',
            'items.costCode',
            'activeWorkflowInstance.template',
            'activeWorkflowInstance.instanceStages.stage.roles',
            'activeWorkflowInstance.instanceStages.stage.fallbackRoles',
            'activeWorkflowInstance.instanceStages.recoveryRoles',
            'activities.causer',
            'comments.user',
        ])->findOrFail($id);
    }

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
            ->whereDate('payment_requests.disbursed_at', '>=', $dateFrom)
            ->whereDate('payment_requests.disbursed_at', '<=', $dateTo)
            ->when($type, fn(QueryBuilder $query) => $query->where('payment_requests.type', $type));

        if ($groupBy === 'branch') {
            return (clone $base)
                ->join('branches', 'payment_requests.branch_id', '=', 'branches.id')
                ->whereNull('branches.deleted_at')
                ->selectRaw('branches.id as group_id, branches.name as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total')
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
                ->whereDate('payment_requests.disbursed_at', '>=', $dateFrom)
                ->whereDate('payment_requests.disbursed_at', '<=', $dateTo)
                ->when($type, fn(QueryBuilder $query) => $query->where('payment_requests.type', $type))
                ->selectRaw('cost_codes.id as group_id, CONCAT(cost_codes.code, \' - \', cost_codes.name) as label, COUNT(DISTINCT payment_requests.id) as count, SUM(payment_request_items.amount) as total')
                ->groupBy('cost_codes.id', 'cost_codes.code', 'cost_codes.name')
                ->orderByDesc('total')
                ->get();
        }

        return (clone $base)
            ->join('staff', 'payment_requests.staff_id', '=', 'staff.id')
            ->join('departments', 'staff.department_id', '=', 'departments.id')
            ->whereNull('staff.deleted_at')
            ->selectRaw('departments.id as group_id, departments.name as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total')
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
            ->whereDoesntHave('retirementRequests', fn(EloquentBuilder $query) => $query->where('status', 'approved'))
            ->orderBy('disbursed_at')
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     * @param ?string $method
     * @param int $perPage
     *
     * @return LengthAwarePaginator<int, PaymentRequest>
     */
    public function disbursementRegister(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
        string|null $method,
        int $perPage = 50,
    ): LengthAwarePaginator {
        return PaymentRequest::with(['staff', 'branch', 'currency', 'disbursedBy'])
            ->where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereDate('disbursed_at', '>=', $dateFrom)
            ->whereDate('disbursed_at', '<=', $dateTo)
            ->when($branchId, fn(EloquentBuilder $query) => $query->where('branch_id', $branchId))
            ->when($method, fn(EloquentBuilder $query) => $query->where('disbursement_method', $method))
            ->orderByDesc('disbursed_at')
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string|null $dateFrom
     * @param string|null $dateTo
     *
     * @return EloquentCollection<int, PaymentRequest>
     */
    public function paymentStatuses(array $allowedBranchIds, string|null $dateFrom = null, string|null $dateTo = null): EloquentCollection
    {
        return PaymentRequest::whereIn('branch_id', $allowedBranchIds)
            ->when($dateFrom !== null && $dateTo !== null, fn($q) => $q->whereDate('created_at', '>=', $dateFrom)->whereDate('created_at', '<=', $dateTo))
            ->select('status', DB::raw('COUNT(*) as count, SUM(total_amount) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     * @param string|null $type
     *
     * @return Collection<int, \stdClass>
     */
    public function deniedCancelledRows(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
        string|null $type,
    ): Collection {
        return DB::table('payment_requests')
            ->join('branches', 'payment_requests.branch_id', '=', 'branches.id')
            ->whereIn('payment_requests.branch_id', $allowedBranchIds)
            ->whereIn('payment_requests.status', ['denied', 'cancelled'])
            ->whereDate('payment_requests.updated_at', '>=', $dateFrom)
            ->whereDate('payment_requests.updated_at', '<=', $dateTo)
            ->whereNull('payment_requests.deleted_at')
            ->when($branchId, fn(QueryBuilder $q) => $q->where('payment_requests.branch_id', $branchId))
            ->when($type, fn(QueryBuilder $q) => $q->where('payment_requests.type', $type))
            ->selectRaw('branches.id as branch_id, branches.name as branch_name, payment_requests.type, payment_requests.status, COUNT(*) as count, SUM(payment_requests.total_amount) as total')
            ->groupBy('branches.id', 'branches.name', 'payment_requests.type', 'payment_requests.status')
            ->orderBy('branches.name')
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
            ->whereDate('approved_at', '>=', $dateFrom)
            ->whereDate('approved_at', '<=', $dateTo)
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
                ->whereDate('payment_requests.disbursed_at', '>=', $dateFrom)
                ->whereDate('payment_requests.disbursed_at', '<=', $dateTo)
                ->when($type, fn(QueryBuilder $query) => $query->where('payment_requests.type', $type))
                ->selectRaw('departments.id as group_id, departments.name as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total')
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
            ->whereDate('payment_requests.disbursed_at', '>=', $dateFrom)
            ->whereDate('payment_requests.disbursed_at', '<=', $dateTo)
            ->when($type, fn($query) => $query->where('payment_requests.type', $type))
            ->selectRaw("staff.id as group_id, CONCAT(staff.first_name, ' ', staff.last_name) as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total")
            ->groupBy('staff.id', 'staff.first_name', 'staff.last_name')
            ->orderByDesc('total')
            ->limit(20)
            ->get();
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
     * @param int $perPage
     *
     * @return LengthAwarePaginator<int, PaymentRequest>
     */
    public function breakdown(
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
        int $perPage = 50,
    ): LengthAwarePaginator {
        return PaymentRequest::with(['staff.department', 'branch', 'currency'])
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereIn('status', $statuses)
            ->whereDate($dateField, '>=', $dateFrom)
            ->whereDate($dateField, '<=', $dateTo)
            ->when($branchId, fn(EloquentBuilder $q) => $q->where('branch_id', $branchId))
            ->when($staffId, fn(EloquentBuilder $q) => $q->where('staff_id', $staffId))
            ->when($departmentId, fn(EloquentBuilder $q) => $q->whereHas('staff', fn(EloquentBuilder $s) => $s->where('department_id', $departmentId)))
            ->when($costCodeId, fn(EloquentBuilder $q) => $q->whereHas('items', fn(EloquentBuilder $i) => $i->where('cost_code_id', $costCodeId)))
            ->when($type, fn(EloquentBuilder $q) => $q->where('type', $type))
            ->orderByDesc($dateField)
            ->paginate($perPage)
            ->withQueryString();
    }
}
