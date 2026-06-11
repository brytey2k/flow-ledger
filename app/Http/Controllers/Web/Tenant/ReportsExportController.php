<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowAction;
use App\Services\BranchScopeService;
use App\Services\ReportService;
use App\Services\RetirementReminderService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsExportController extends Controller
{
    public function __construct(
        private readonly ReportService $reports,
        private readonly RetirementReminderService $retirementReminders,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function expenditureSummary(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->expenditureSummary(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->stringInput($request, 'group_by', 'department'),
            $this->nullableStringInput($request, 'type'),
        );

        $groupBy = $data['groupBy'];
        $grandTotal = $data['grandTotal'];
        $headers = [ucfirst(str_replace('_', ' ', $groupBy)), 'Requests', 'Total Amount', '% of Total'];
        $rows = $data['rows']->map(fn(\stdClass $row) => [
            $row->label,
            $row->count,
            number_format($this->toFloat($row->total), 2, '.', ''),
            $grandTotal > 0 ? round(($this->toFloat($row->total) / $grandTotal) * 100, 1) : 0,
        ]);

        return $this->export($request, 'expenditure-summary', $headers, $rows, $data, 'Expenditure Summary');
    }

    public function outstandingAdvances(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->outstandingAdvances(
            $allowedBranchIds,
            $this->nullableBranchIdInput($request),
        );

        $headers = ['Request #', 'Staff', 'Branch', 'Type', 'Amount', 'Days Outstanding', 'Bucket'];
        $rows = $data['advances']->map(fn(array $item) => [
            $item['request']->id,
            $item['request']->staff->full_name ?? '',
            $item['request']->branch->name ?? '',
            $item['request']->type ?? '',
            number_format((float) $item['request']->total_amount, 2, '.', ''),
            $item['days'],
            $item['bucket'],
        ]);

        return $this->export($request, 'outstanding-advances', $headers, $rows, $data, 'Outstanding Advances');
    }

    public function cashPosition(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->cashPosition(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        );

        $headers = ['Cashbook', 'Current Balance', 'Period Debits', 'Period Credits', 'Entries'];
        $rows = $data['cashbooks']->map(fn(array $item) => [
            $item['cashbook']->branch->name,
            number_format($item['current_balance'], 2, '.', ''),
            number_format($item['period_debits'], 2, '.', ''),
            number_format($item['period_credits'], 2, '.', ''),
            $item['entry_count'],
        ]);

        return $this->export($request, 'cash-position', $headers, $rows, $data, 'Cash Position');
    }

    public function disbursementRegister(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->disbursementRegister(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
            $this->nullableStringInput($request, 'method'),
            PHP_INT_MAX,
        );

        $headers = ['#', 'Staff', 'Branch', 'Type', 'Amount', 'Method', 'Reference', 'Disbursed By', 'Date'];
        $rows = collect($data['disbursements']->items())->map(fn(PaymentRequest $req) => [
            $req->id,
            $req->staff->full_name ?? '',
            $req->branch->name ?? '',
            $req->type ?? '',
            number_format((float) $req->total_amount, 2, '.', ''),
            $req->disbursement_method ?? '',
            $req->disbursement_reference ?? '',
            $req->disbursedBy->name ?? '',
            $req->disbursed_at?->format('Y-m-d') ?? '',
        ]);

        return $this->export($request, 'disbursement-register', $headers, $rows, $data, 'Disbursement Register');
    }

    public function approvalTurnaround(Request $request): Response|StreamedResponse
    {
        $data = $this->reports->approvalTurnaround(
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        );

        $headers = ['Stage', 'Count', 'Approved', 'Sent Back', 'Avg Hours', 'Min Hours', 'Max Hours'];
        $rows = $data['stages']->map(fn(array $stage) => [
            $stage['stage_name'],
            $stage['count'],
            $stage['approved'],
            $stage['sent_back'],
            $stage['avg_hours'],
            $stage['min_hours'],
            $stage['max_hours'],
        ]);

        return $this->export($request, 'approval-turnaround', $headers, $rows, $data, 'Approval Turnaround');
    }

    public function pendingRequestsAging(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->pendingRequestsAging($allowedBranchIds);

        $headers = ['Stage', 'Request #', 'Days Pending', 'Bucket'];

        /** @var \Illuminate\Support\Collection<int, array{stage: \App\Models\Tenant\WorkflowInstanceStage, days: int, bucket: string}> $activeStages */
        $activeStages = $data['activeStages'];
        $rows = $activeStages->map(fn(array $item) => [
            $item['stage']->stage->name ?? 'Unknown',
            $item['stage']->instance?->workflowable?->getKey() ?? '',
            $item['days'],
            $item['bucket'],
        ]);

        return $this->export($request, 'pending-requests-aging', $headers, $rows, $data, 'Pending Requests Aging');
    }

    public function sendBackRate(Request $request): Response|StreamedResponse
    {
        $data = $this->reports->sendBackRate(
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        );

        $headers = ['User', 'Total Actions', 'Sent Back', 'Rate (%)'];
        $rows = $data['rows']->map(fn(array $row) => [
            $row['user']->name ?? '',
            $row['total_actions'],
            $row['sent_back_count'],
            $row['rate'],
        ]);

        return $this->export($request, 'send-back-rate', $headers, $rows, $data, 'Send-Back Rate');
    }

    public function auditTrail(Request $request): Response|StreamedResponse
    {
        $data = $this->reports->auditTrail(
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableStringInput($request, 'action'),
            PHP_INT_MAX,
        );

        $headers = ['Date', 'User', 'Action', 'Request #', 'Stage'];
        $rows = collect($data['actions']->items())->map(fn(WorkflowAction $action) => [
            date('Y-m-d H:i', (int) strtotime($action->created_at)),
            $action->user->name ?? '',
            $action->action ?? '',
            $action->instanceStage?->instance?->workflowable?->getKey() ?? '',
            $action->instanceStage?->stage->name ?? '',
        ]);

        return $this->export($request, 'audit-trail', $headers, $rows, $data, 'Audit Trail');
    }

    public function requestsByStatus(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->requestsByStatus(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfYear()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        );

        $headers = ['Category', 'Status', 'Count'];
        $rows = collect($data['paymentStatuses'])->map(fn(PaymentRequest $row) => ['Payment Request', $row->status, $row->getAttribute('count')])
            ->concat(collect($data['retirementStatuses'])->map(fn(RetirementRequest $row) => ['Retirement Request', $row->status, $row->getAttribute('count')]));

        return $this->export($request, 'requests-by-status', $headers, $rows, $data, 'Requests by Status');
    }

    public function retirementVariance(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->retirementVariance(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
        );

        $headers = ['Request #', 'Staff', 'Branch', 'Disbursed Amount', 'Amount Expended', 'Difference'];
        $rows = $data['rows']->map(fn(\stdClass $row) => [
            $row->payment_request_id ?? $row->id ?? '',
            $row->staff_name ?? '',
            $row->branch_name ?? '',
            number_format($this->toFloat($row->disbursed_amount), 2, '.', ''),
            number_format($this->toFloat($row->total_amount_expended), 2, '.', ''),
            number_format($this->toFloat($row->difference_amount), 2, '.', ''),
        ]);

        return $this->export($request, 'retirement-variance', $headers, $rows, $data, 'Retirement Variance');
    }

    public function deniedCancelledAnalysis(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->deniedCancelledAnalysis(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
            $this->nullableStringInput($request, 'type'),
        );

        $headers = ['Branch', 'Status', 'Count'];
        $rows = $data['rows']->map(fn(\stdClass $row) => [
            $row->branch_name,
            $row->status,
            $row->count,
        ]);

        return $this->export($request, 'denied-cancelled', $headers, $rows, $data, 'Denied & Cancelled Analysis');
    }

    public function retirementTurnaround(Request $request): Response|StreamedResponse
    {
        $data = $this->reports->retirementTurnaround(
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
        );

        $headers = ['Stage', 'Count', 'Approved', 'Sent Back', 'Avg Hours', 'Min Hours', 'Max Hours'];
        $rows = $data['stages']->map(fn(array $stage) => [
            $stage['stage_name'],
            $stage['count'],
            $stage['approved'],
            $stage['sent_back'],
            $stage['avg_hours'],
            $stage['min_hours'],
            $stage['max_hours'],
        ]);

        return $this->export($request, 'retirement-turnaround', $headers, $rows, $data, 'Retirement Turnaround');
    }

    public function workflowSla(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->workflowSla(
            $allowedBranchIds,
            $this->integerInput($request, 'sla_days', 3),
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableStringInput($request, 'type'),
        );

        $headers = ['Request #', 'Staff', 'Branch', 'Type', 'Days', 'Compliant'];
        $rows = $data['requests']->map(fn(array $item) => [
            $item['request']->id,
            $item['request']->staff->full_name ?? '',
            $item['request']->branch->name ?? '',
            $item['request']->type ?? '',
            $item['days'],
            $item['compliant'] ? 'Yes' : 'No',
        ]);

        return $this->export($request, 'workflow-sla', $headers, $rows, $data, 'Workflow SLA');
    }

    public function spendTrend(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->spendTrend(
            $allowedBranchIds,
            $this->integerInput($request, 'year', now()->year),
            $this->nullableStringInput($request, 'type'),
        );

        $headers = ['Month', 'Total Amount', 'Request Count'];
        $rows = $data['rows']->map(fn(PaymentRequest $row) => [
            $row->getAttribute('month_label'),
            number_format($this->toFloat($row->getAttribute('total')), 2, '.', ''),
            $row->getAttribute('count'),
        ]);

        return $this->export($request, 'spend-trend', $headers, $rows, $data, 'Spend Trend');
    }

    public function topSpenders(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->topSpenders(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->stringInput($request, 'group_by', 'staff'),
            $this->nullableStringInput($request, 'type'),
        );

        $groupBy = $data['groupBy'];
        $headers = [ucfirst($groupBy), 'Total Amount', 'Request Count'];
        $rows = $data['rows']->map(fn(\stdClass $row) => [
            $row->label,
            number_format($this->toFloat($row->total), 2, '.', ''),
            $row->count,
        ]);

        return $this->export($request, 'top-spenders', $headers, $rows, $data, 'Top Spenders');
    }

    public function retirementReminders(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->retirementReminders->getReport(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
        );

        $headers = ['Request #', 'Staff', 'Branch', 'Amount', 'Disbursed At', 'Reminders Sent', 'Last Reminder Date'];
        $rows = $data['rows']->map(fn(\stdClass $row) => [
            $row->payment_request_id,
            $row->staff_name,
            $row->branch_name,
            number_format($this->toFloat($row->total_amount), 2, '.', ''),
            is_string($row->disbursed_at) && $row->disbursed_at ? date('Y-m-d', (int) strtotime($row->disbursed_at)) : '',
            $row->reminder_count,
            $row->last_reminder_date ?? '',
        ]);

        return $this->export($request, 'retirement-reminders', $headers, $rows, $data, 'Retirement Reminders');
    }

    public function paymentRequestBreakdown(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $rawStatuses = $this->stringInput($request, 'statuses', 'disbursed');
        $statuses = array_values(array_filter(explode(',', $rawStatuses)));

        $allowedDateFields = ['disbursed_at', 'created_at', 'updated_at', 'approved_at'];
        $dateField = $this->stringInput($request, 'date_field', 'disbursed_at');
        if (! in_array($dateField, $allowedDateFields, true)) {
            $dateField = 'disbursed_at';
        }

        $data = $this->reports->paymentRequestBreakdown(
            $allowedBranchIds,
            $statuses ?: ['disbursed'],
            $dateField,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
            $this->nullableIntInput($request, 'staff_id'),
            $this->nullableIntInput($request, 'department_id'),
            $this->nullableIntInput($request, 'cost_code_id'),
            $this->nullableStringInput($request, 'type'),
            $this->stringInput($request, 'title', 'Payment Requests'),
            PHP_INT_MAX,
        );

        $headers = ['#', 'Staff', 'Department', 'Branch', 'Type', 'Status', 'Amount', 'Date'];
        $rows = collect($data['rows']->items())->map(fn(PaymentRequest $req) => [
            $req->id,
            $req->staff->full_name ?? '',
            $req->staff->department->name ?? '',
            $req->branch->name ?? '',
            $req->type ?? '',
            $req->status ?? '',
            number_format((float) $req->total_amount, 2, '.', ''),
            $req->{$dateField}?->format('Y-m-d') ?? '',
        ]);

        return $this->export($request, 'breakdown', $headers, $rows, $data, $data['title']);
    }

    public function cashCountReport(Request $request): Response|StreamedResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);

        $data = $this->reports->cashCountReport(
            $allowedBranchIds,
            $this->dateInput($request, 'date_from', now()->startOfMonth()->toDateString()),
            $this->dateInput($request, 'date_to', now()->toDateString()),
            $this->nullableBranchIdInput($request),
        );

        $headers = ['Date', 'Branch', 'Opening Balance', 'Counted Amount', 'Difference'];
        $rows = $data['rows']->map(function (CashCount $row): array {
            $branchRelation = $row->getRelation('branch');
            $branchName = $branchRelation instanceof \App\Models\Tenant\Branch ? $branchRelation->name : '';

            return [
                $row->count_date ?? $row->created_at ?? '',
                $branchName,
                number_format($this->toFloat($row->opening_balance ?? 0), 2, '.', ''),
                number_format($this->toFloat($row->counted_amount ?? 0), 2, '.', ''),
                number_format($this->toFloat($row->difference ?? 0), 2, '.', ''),
            ];
        });

        return $this->export($request, 'cash-count', $headers, $rows, $data, 'Cash Count Report');
    }

    /**
     * @param Request $request
     * @param string $slug
     * @param array<int, string> $headers
     * @param iterable<int, mixed> $rows
     * @param array<string, mixed> $data
     * @param string $title
     */
    private function export(
        Request $request,
        string $slug,
        array $headers,
        iterable $rows,
        array $data,
        string $title,
    ): Response|StreamedResponse {
        $format = $request->input('format', 'csv');
        $date = now()->format('Y-m-d');

        if ($format === 'pdf') {
            return $this->renderPdf(
                'tenant.reports.exports.generic',
                array_merge($data, ['exportTitle' => $title, 'exportHeaders' => $headers, 'exportRows' => $rows]),
                "{$slug}-{$date}.pdf",
            );
        }

        return $this->streamCsv($headers, $rows, "{$slug}-{$date}.csv");
    }

    /**
     * @param array<int, string> $headers
     * @param iterable<int, mixed> $rows
     * @param string $filename
     */
    private function streamCsv(array $headers, iterable $rows, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($headers, $rows): void {
            $handle = fopen('php://output', 'w');
            assert($handle !== false);
            fputcsv($handle, $headers);
            foreach ($rows as $row) {
                // @phpstan-ignore-next-line
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    /**
     * @param string $view
     * @param array<string, mixed> $data
     * @param string $filename
     */
    private function renderPdf(string $view, array $data, string $filename): Response
    {
        return Pdf::loadView($view, $data)
            ->setPaper('a4', 'landscape')
            ->download($filename);
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

    private function nullableIntInput(Request $request, string $key): int|null
    {
        if (! $request->filled($key)) {
            return null;
        }

        return (int) $request->integer($key);
    }

    private function integerInput(Request $request, string $key, int $default): int
    {
        return (int) $request->integer($key, $default);
    }

    private function toFloat(mixed $value): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }
}
