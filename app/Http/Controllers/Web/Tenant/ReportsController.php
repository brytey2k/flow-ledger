<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Services\BranchScopeService;
use App\Services\ReportService;
use App\Services\RetirementReminderService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReportsController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly RetirementReminderService $retirementReminders,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(): View
    {
        return view('tenant.reports.index');
    }

    public function expenditureSummary(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.expenditure-summary', $this->reports->expenditureSummary(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->stringInput($request, 'group_by', 'department'),
            $this->nullableStringInput($request, 'type'),
        ));
    }

    public function outstandingAdvances(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.outstanding-advances', $this->reports->outstandingAdvances(
            $allowedBranchIds,
            $this->nullableBranchIdInput($request),
        ));
    }

    public function cashPosition(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.cash-position', $this->reports->cashPosition(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        ));
    }

    public function disbursementRegister(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.disbursement-register', $this->reports->disbursementRegister(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
            $this->nullableStringInput($request, 'method'),
        ));
    }

    public function approvalTurnaround(Request $request): View
    {
        return view('tenant.reports.approval-turnaround', $this->reports->approvalTurnaround(
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        ));
    }

    public function pendingRequestsAging(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.pending-requests-aging', $this->reports->pendingRequestsAging($allowedBranchIds));
    }

    public function sendBackRate(Request $request): View
    {
        return view('tenant.reports.send-back-rate', $this->reports->sendBackRate(
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        ));
    }

    public function auditTrail(Request $request): View
    {
        return view('tenant.reports.audit-trail', $this->reports->auditTrail(
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableStringInput($request, 'action'),
        ));
    }

    public function requestsByStatus(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.requests-by-status', $this->reports->requestsByStatus(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfYear()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        ));
    }

    public function retirementVariance(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.retirement-variance', $this->reports->retirementVariance(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
        ));
    }

    public function deniedCancelledAnalysis(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.denied-cancelled', $this->reports->deniedCancelledAnalysis(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
            $this->nullableStringInput($request, 'type'),
        ));
    }

    public function retirementTurnaround(Request $request): View
    {
        return view('tenant.reports.retirement-turnaround', $this->reports->retirementTurnaround(
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        ));
    }

    public function workflowSla(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.workflow-sla', $this->reports->workflowSla(
            $allowedBranchIds,
            $this->integerInput($request, 'sla_days', 3),
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableStringInput($request, 'type'),
        ));
    }

    public function spendTrend(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.spend-trend', $this->reports->spendTrend(
            $allowedBranchIds,
            $this->integerInput($request, 'year', now()->year),
            $this->nullableStringInput($request, 'type'),
        ));
    }

    public function topSpenders(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.top-spenders', $this->reports->topSpenders(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->stringInput($request, 'group_by', 'staff'),
            $this->nullableStringInput($request, 'type'),
        ));
    }

    public function retirementReminders(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        return view('tenant.reports.retirement-reminders', $this->retirementReminders->getReport(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
        ));
    }

    private function dateInput(Request $request, string $key, string $default): string
    {
        return $request->string($key, $default)->toString();
    }

    private function stringInput(Request $request, string $key, string $default): string
    {
        return $request->string($key, $default)->toString();
    }

    private function nullableStringInput(Request $request, string $key): string|null
    {
        if (! $request->filled($key)) {
            return null;
        }

        return $request->string($key)->toString();
    }

    private function nullableBranchIdInput(Request $request): string|null
    {
        if (! $request->filled('branch_id')) {
            return null;
        }

        return $request->string('branch_id')->toString();
    }

    private function integerInput(Request $request, string $key, int $default): int
    {
        return (int) $request->integer($key, $default);
    }
}
