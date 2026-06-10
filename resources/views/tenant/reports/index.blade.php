@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Reports</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Select a report below to gain insights into your organisation's financial activity and workflow performance.
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Financial Visibility --}}
        <div>
            <h2 class="text-base font-semibold text-mono mb-4">Financial Visibility</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">

                <a href="{{ route('reports.expenditure-summary') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                            <i class="ki-filled ki-chart-pie-simple text-xl text-primary"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Expenditure Summary</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Spend by department, branch, or cost code over a selected period. Filter by request type (advance vs expense).
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.outstanding-advances') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-warning/10">
                            <i class="ki-filled ki-time text-xl text-warning"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Outstanding Advances</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Staff with disbursed advances that have no approved retirement. Includes aging buckets (0–30, 31–60, 61+ days).
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.cash-position') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-success/10">
                            <i class="ki-filled ki-calculator text-xl text-success"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Cash Position</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Current balance per cashbook with period in/out totals. Shows net cash movement for any date range.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.disbursement-register') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-info/10">
                            <i class="ki-filled ki-bank text-xl text-info"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Disbursement Register</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        All disbursed payments in a date range: amount, method, reference, recipient, and who disbursed. The auditor's first ask.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.retirement-reminders') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-warning/10">
                            <i class="ki-filled ki-notification text-xl text-warning"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Retirement Reminders</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Advances that have triggered overdue retirement reminders. Shows reminder frequency and the last date notified.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.retirement-variance') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-success/10">
                            <i class="ki-filled ki-arrows-diagonal text-xl text-success"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Retirement Variance</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Disbursed advance amount vs. actual spend in the approved retirement. Shows over- and under-spend per advance for reconciliation.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.cash-count') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                            <i class="ki-filled ki-calculator text-xl text-primary"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Cash Count Report</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        All physical cash counts for a period: book balance vs. counted total, discrepancies, and who performed each count.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

            </div>
        </div>

        {{-- Process & Efficiency --}}
        <div>
            <h2 class="text-base font-semibold text-mono mb-4">Process &amp; Efficiency</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">

                <a href="{{ route('reports.approval-turnaround') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                            <i class="ki-filled ki-arrows-loop text-xl text-primary"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Approval Turnaround</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Average time from submission to approval per workflow stage. Shows where bottlenecks are forming.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.pending-requests-aging') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-warning/10">
                            <i class="ki-filled ki-sandwatch text-xl text-warning"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Pending Requests Aging</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Requests currently in workflow grouped by how long they have been waiting. Flags stuck approvals.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.send-back-rate') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-danger/10">
                            <i class="ki-filled ki-arrow-left text-xl text-danger"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Send-Back Rate</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Percentage of reviews that result in a send-back per approver. High rates signal training gaps or submission quality issues.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

            </div>
        </div>

        {{-- Compliance & Audit --}}
        <div>
            <h2 class="text-base font-semibold text-mono mb-4">Compliance &amp; Audit</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-4">

                <a href="{{ route('reports.audit-trail') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-neutral/10">
                            <i class="ki-filled ki-shield text-xl text-foreground"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Audit Trail</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Full workflow action history — who approved, sent back, or cancelled, with timestamps and comments. Required for internal and external audits.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.requests-by-status') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-info/10">
                            <i class="ki-filled ki-element-11 text-xl text-info"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Requests by Status</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Count and total value of all requests in each status for any date range. A filterable pipeline distribution snapshot.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.denied-cancelled') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-danger/10">
                            <i class="ki-filled ki-cross-circle text-xl text-danger"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Denied &amp; Cancelled</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Count and value of denied and cancelled requests grouped by branch and type. Surfaces which areas have the highest failure rates.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.retirement-turnaround') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                            <i class="ki-filled ki-arrows-loop text-xl text-primary"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Retirement Turnaround</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Average time per retirement workflow stage from submission to decision. Shows bottlenecks in the retirement approval process.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

            </div>
        </div>

        {{-- Executive --}}
        <div>
            <h2 class="text-base font-semibold text-mono mb-4">Executive</h2>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-2 xl:grid-cols-3">

                <a href="{{ route('reports.workflow-sla') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-success/10">
                            <i class="ki-filled ki-shield-tick text-xl text-success"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Workflow SLA</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Percentage of requests approved within a configurable target (default: 3 business days). A compliance KPI management can act on.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.spend-trend') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-primary/10">
                            <i class="ki-filled ki-chart-line-up text-xl text-primary"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Spend Trend</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Total spend per month across all request types. Reveals seasonal patterns and year-over-year changes in expenditure.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

                <a href="{{ route('reports.top-spenders') }}" class="kt-card kt-card-hover p-5 flex flex-col gap-3 transition-shadow hover:shadow-md">
                    <div class="flex items-center gap-3">
                        <div class="flex size-10 items-center justify-center rounded-lg bg-warning/10">
                            <i class="ki-filled ki-ranking text-xl text-warning"></i>
                        </div>
                        <h3 class="text-sm font-semibold text-mono">Top Spenders</h3>
                    </div>
                    <p class="text-xs text-secondary-foreground leading-relaxed">
                        Staff or departments with the highest approved spend in a period. Useful for budget conversations and spend reviews.
                    </p>
                    <span class="text-xs font-medium text-primary flex items-center gap-1">View report <i class="ki-filled ki-arrow-right text-xs"></i></span>
                </a>

            </div>
        </div>

    </div>
</div>
@endsection
