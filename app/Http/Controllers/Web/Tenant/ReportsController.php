<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Models\Tenant\Cashbook;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Services\BranchScopeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ReportsController
{
    public function __construct(private readonly BranchScopeService $branchScope) {}

    public function index(): View
    {
        return view('tenant.reports.index');
    }

    public function expenditureSummary(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $groupBy = $request->input('group_by', 'department');
        $type = $request->input('type');

        $base = DB::table('payment_requests')
            ->where('payment_requests.status', 'disbursed')
            ->whereNull('payment_requests.deleted_at')
            ->whereIn('payment_requests.branch_id', $allowedBranchIds)
            ->whereBetween('payment_requests.disbursed_at', [$dateFrom, $dateTo])
            ->when($type, fn($q) => $q->where('payment_requests.type', $type));

        if ($groupBy === 'branch') {
            $rows = (clone $base)
                ->join('branches', 'payment_requests.branch_id', '=', 'branches.id')
                ->whereNull('branches.deleted_at')
                ->selectRaw('branches.name as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total')
                ->groupBy('branches.id', 'branches.name')
                ->orderByDesc('total')
                ->get();
        } elseif ($groupBy === 'account_code') {
            $rows = DB::table('payment_request_items')
                ->join('payment_requests', 'payment_request_items.payment_request_id', '=', 'payment_requests.id')
                ->join('account_codes', 'payment_request_items.account_code_id', '=', 'account_codes.id')
                ->where('payment_requests.status', 'disbursed')
                ->whereNull('payment_requests.deleted_at')
                ->whereIn('payment_requests.branch_id', $allowedBranchIds)
                ->whereBetween('payment_requests.disbursed_at', [$dateFrom, $dateTo])
                ->when($type, fn($q) => $q->where('payment_requests.type', $type))
                ->selectRaw('CONCAT(account_codes.code, \' - \', account_codes.name) as label, COUNT(DISTINCT payment_requests.id) as count, SUM(payment_request_items.amount) as total')
                ->groupBy('account_codes.id', 'account_codes.code', 'account_codes.name')
                ->orderByDesc('total')
                ->get();
        } else {
            $rows = (clone $base)
                ->join('staff', 'payment_requests.staff_id', '=', 'staff.id')
                ->join('departments', 'staff.department_id', '=', 'departments.id')
                ->whereNull('staff.deleted_at')
                ->selectRaw('departments.name as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total')
                ->groupBy('departments.id', 'departments.name')
                ->orderByDesc('total')
                ->get();
        }

        $grandTotal = $rows->sum('total');

        return view('tenant.reports.expenditure-summary', compact('rows', 'grandTotal', 'dateFrom', 'dateTo', 'groupBy', 'type'));
    }

    public function outstandingAdvances(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $branchId = $request->input('branch_id');

        $advances = PaymentRequest::query()
            ->with(['staff.department', 'branch', 'currency', 'retirementRequest'])
            ->where('type', 'advance')
            ->where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->whereDoesntHave('retirementRequest', fn($q) => $q->whereIn('status', ['approved', 'settled']))
            ->orderBy('disbursed_at')
            ->get()
            ->map(function (PaymentRequest $req) {
                $days = $req->disbursed_at ? $req->disbursed_at->diffInDays(now()) : 0;
                $bucket = match (true) {
                    $days <= 30 => '0–30 days',
                    $days <= 60 => '31–60 days',
                    default => '61+ days',
                };

                return ['request' => $req, 'days' => $days, 'bucket' => $bucket];
            });

        $buckets = $advances->groupBy('bucket');
        $branches = DB::table('branches')->whereNull('deleted_at')->whereIn('id', $allowedBranchIds)->orderBy('name')->pluck('name', 'id');

        return view('tenant.reports.outstanding-advances', compact('advances', 'buckets', 'branches', 'branchId'));
    }

    public function cashPosition(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $cashbooks = Cashbook::with(['branch', 'currency'])
            ->whereIn('branch_id', $allowedBranchIds)
            ->withCount('entries')
            ->get()
            ->map(function (Cashbook $book) use ($dateFrom, $dateTo) {
                $entries = $book->entries()
                    ->whereBetween('entry_date', [$dateFrom, $dateTo])
                    ->whereNull('deleted_at')
                    ->get();

                return [
                    'cashbook' => $book,
                    'current_balance' => $book->balance,
                    'period_debits' => $entries->where('type', 'debit')->sum('amount'),
                    'period_credits' => $entries->where('type', 'credit')->sum('amount'),
                    'entry_count' => $entries->count(),
                ];
            });

        return view('tenant.reports.cash-position', compact('cashbooks', 'dateFrom', 'dateTo'));
    }

    public function disbursementRegister(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $branchId = $request->input('branch_id');
        $method = $request->input('method');

        $disbursements = PaymentRequest::with(['staff', 'branch', 'currency', 'disbursedBy'])
            ->where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereBetween('disbursed_at', [$dateFrom, $dateTo])
            ->when($branchId, fn($q) => $q->where('branch_id', $branchId))
            ->when($method, fn($q) => $q->where('disbursement_method', $method))
            ->orderByDesc('disbursed_at')
            ->paginate(50)
            ->withQueryString();

        $branches = DB::table('branches')->whereNull('deleted_at')->whereIn('id', $allowedBranchIds)->orderBy('name')->pluck('name', 'id');
        $methods = \App\Enums\Tenant\PaymentMethod::cases();

        return view('tenant.reports.disbursement-register', compact('disbursements', 'dateFrom', 'dateTo', 'branches', 'branchId', 'method', 'methods'));
    }

    public function approvalTurnaround(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $stages = WorkflowInstanceStage::with('stage')
            ->whereIn('status', ['approved', 'sent_back', 'cancelled'])
            ->whereNotNull('completed_at')
            ->whereNotNull('started_at')
            ->whereBetween('completed_at', [$dateFrom, $dateTo])
            ->get()
            ->groupBy('workflow_stage_id')
            ->map(function ($group) {
                $hours = $group->map(fn(WorkflowInstanceStage $s) => $s->started_at->diffInMinutes($s->completed_at) / 60);
                $approved = $group->where('status', 'approved')->count();
                $sentBack = $group->where('status', 'sent_back')->count();

                return [
                    'stage_name' => $group->first()->stage->name ?? 'Unknown',
                    'count' => $group->count(),
                    'approved' => $approved,
                    'sent_back' => $sentBack,
                    'avg_hours' => round($hours->avg() ?? 0, 1),
                    'min_hours' => round($hours->min() ?? 0, 1),
                    'max_hours' => round($hours->max() ?? 0, 1),
                ];
            })
            ->sortByDesc('avg_hours')
            ->values();

        return view('tenant.reports.approval-turnaround', compact('stages', 'dateFrom', 'dateTo'));
    }

    public function pendingRequestsAging(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $activeStages = WorkflowInstanceStage::with([
            'stage',
            'instance.workflowable',
            'instance.workflowable.staff',
            'instance.workflowable.branch',
            'instance.workflowable.currency',
        ])
            ->where('status', 'active')
            ->whereNotNull('started_at')
            ->orderBy('started_at')
            ->get()
            ->filter(fn(WorkflowInstanceStage $stage) => in_array(
                $stage->instance?->workflowable?->getAttribute('branch_id'),
                $allowedBranchIds,
                true,
            ))
            ->map(function (WorkflowInstanceStage $stage) {
                $days = $stage->started_at->diffInDays(now());
                $bucket = match (true) {
                    $days <= 3 => '0–3 days',
                    $days <= 7 => '4–7 days',
                    $days <= 14 => '8–14 days',
                    default => '15+ days',
                };

                return ['stage' => $stage, 'days' => $days, 'bucket' => $bucket];
            });

        $bucketCounts = $activeStages->countBy('bucket');

        return view('tenant.reports.pending-requests-aging', compact('activeStages', 'bucketCounts'));
    }

    public function sendBackRate(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());

        $totalByUser = WorkflowAction::with('user')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('user_id', DB::raw('COUNT(*) as total_actions'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $sentBackByUser = WorkflowAction::with('user')
            ->where('action', 'sent_back')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('user_id', DB::raw('COUNT(*) as sent_back_count'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $rows = $totalByUser->map(function ($row) use ($sentBackByUser) {
            $totalActionsRaw = $row->getAttribute('total_actions');
            $totalActions = is_numeric($totalActionsRaw) ? (int) $totalActionsRaw : 0;
            $sentBackRaw = $sentBackByUser->get($row->user_id)?->getAttribute('sent_back_count') ?? 0;
            $sentBack = is_numeric($sentBackRaw) ? (int) $sentBackRaw : 0;
            $rate = $totalActions > 0 ? round(($sentBack / $totalActions) * 100, 1) : 0;

            return [
                'user' => $row->user,
                'total_actions' => $totalActions,
                'sent_back_count' => $sentBack,
                'rate' => $rate,
            ];
        })
            ->sortByDesc('sent_back_count')
            ->values();

        return view('tenant.reports.send-back-rate', compact('rows', 'dateFrom', 'dateTo'));
    }

    public function auditTrail(Request $request): View
    {
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $action = $request->input('action');

        $actions = WorkflowAction::with([
            'user',
            'instanceStage.stage',
            'instanceStage.instance.workflowable',
        ])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($action, fn($q) => $q->where('action', $action))
            ->latest('created_at')
            ->paginate(50)
            ->withQueryString();

        $actionTypes = ['approved', 'sent_back', 'cancelled'];

        return view('tenant.reports.audit-trail', compact('actions', 'dateFrom', 'dateTo', 'action', 'actionTypes'));
    }

    public function requestsByStatus(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $paymentStatuses = PaymentRequest::whereIn('branch_id', $allowedBranchIds)
            ->select('status', DB::raw('COUNT(*) as count, SUM(total_amount) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $retirementStatuses = RetirementRequest::whereHas('paymentRequest', fn($q) => $q->whereIn('branch_id', $allowedBranchIds))
            ->select('status', DB::raw('COUNT(*) as count, SUM(total_amount_expended) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();

        $paymentTotal = $paymentStatuses->sum('count');
        $retirementTotal = $retirementStatuses->sum('count');

        return view('tenant.reports.requests-by-status', compact('paymentStatuses', 'retirementStatuses', 'paymentTotal', 'retirementTotal'));
    }

    public function workflowSla(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $slaDays = $request->integer('sla_days', 3);
        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $type = $request->input('type');

        $requests = PaymentRequest::whereNotNull('approved_at')
            ->whereNotNull('submitted_at')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereBetween('approved_at', [$dateFrom, $dateTo])
            ->when($type, fn($q) => $q->where('type', $type))
            ->with(['staff', 'branch', 'currency'])
            ->get()
            ->map(function (PaymentRequest $req) use ($slaDays) {
                $days = $req->submitted_at && $req->approved_at
                    ? $req->submitted_at->diffInDays($req->approved_at)
                    : 0;

                return [
                    'request' => $req,
                    'days' => $days,
                    'compliant' => $days <= $slaDays,
                ];
            });

        $compliantCount = $requests->where('compliant', true)->count();
        $total = $requests->count();
        $complianceRate = $total > 0 ? round(($compliantCount / $total) * 100, 1) : 0;
        $avgDays = $total > 0 ? round((float) $requests->avg('days'), 1) : 0;

        return view('tenant.reports.workflow-sla', compact('requests', 'compliantCount', 'total', 'complianceRate', 'avgDays', 'slaDays', 'dateFrom', 'dateTo', 'type'));
    }

    public function spendTrend(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $year = $request->integer('year', now()->year);
        $type = $request->input('type');

        $rows = PaymentRequest::where('status', 'disbursed')
            ->whereNotNull('disbursed_at')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereYear('disbursed_at', $year)
            ->when($type, fn($q) => $q->where('type', $type))
            ->selectRaw("TO_CHAR(disbursed_at, 'Mon') as month_label, EXTRACT(MONTH FROM disbursed_at) as month_num, SUM(total_amount) as total, COUNT(*) as count")
            ->groupByRaw("TO_CHAR(disbursed_at, 'Mon'), EXTRACT(MONTH FROM disbursed_at)")
            ->orderByRaw('EXTRACT(MONTH FROM disbursed_at)')
            ->get();

        $years = PaymentRequest::where('status', 'disbursed')
            ->whereIn('branch_id', $allowedBranchIds)
            ->whereNotNull('disbursed_at')
            ->selectRaw('EXTRACT(YEAR FROM disbursed_at) as yr')
            ->groupByRaw('EXTRACT(YEAR FROM disbursed_at)')
            ->orderByRaw('EXTRACT(YEAR FROM disbursed_at) DESC')
            ->pluck('yr')
            ->map(fn($y) => is_numeric($y) ? (int) $y : 0);

        if ($years->isEmpty()) {
            $years = collect([now()->year]);
        }

        return view('tenant.reports.spend-trend', compact('rows', 'year', 'years', 'type'));
    }

    public function topSpenders(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $dateFrom = $request->input('date_from', now()->startOfMonth()->toDateString());
        $dateTo = $request->input('date_to', now()->toDateString());
        $groupBy = $request->input('group_by', 'staff');
        $type = $request->input('type');

        if ($groupBy === 'department') {
            $rows = DB::table('payment_requests')
                ->join('staff', 'payment_requests.staff_id', '=', 'staff.id')
                ->join('departments', 'staff.department_id', '=', 'departments.id')
                ->where('payment_requests.status', 'disbursed')
                ->whereNull('payment_requests.deleted_at')
                ->whereNull('staff.deleted_at')
                ->whereIn('payment_requests.branch_id', $allowedBranchIds)
                ->whereBetween('payment_requests.disbursed_at', [$dateFrom, $dateTo])
                ->when($type, fn($q) => $q->where('payment_requests.type', $type))
                ->selectRaw('departments.name as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total')
                ->groupBy('departments.id', 'departments.name')
                ->orderByDesc('total')
                ->limit(20)
                ->get();
        } else {
            $rows = DB::table('payment_requests')
                ->join('staff', 'payment_requests.staff_id', '=', 'staff.id')
                ->where('payment_requests.status', 'disbursed')
                ->whereNull('payment_requests.deleted_at')
                ->whereNull('staff.deleted_at')
                ->whereIn('payment_requests.branch_id', $allowedBranchIds)
                ->whereBetween('payment_requests.disbursed_at', [$dateFrom, $dateTo])
                ->when($type, fn($q) => $q->where('payment_requests.type', $type))
                ->selectRaw("CONCAT(staff.first_name, ' ', staff.last_name) as label, COUNT(payment_requests.id) as count, SUM(payment_requests.total_amount) as total")
                ->groupBy('staff.id', 'staff.first_name', 'staff.last_name')
                ->orderByDesc('total')
                ->limit(20)
                ->get();
        }

        return view('tenant.reports.top-spenders', compact('rows', 'dateFrom', 'dateTo', 'groupBy', 'type'));
    }
}
