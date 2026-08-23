@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Workflow SLA</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Workflow SLA</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Percentage of requests approved within a configurable target number of days (default: 3 days).
                A compliance KPI management can act on to hold approvers accountable and set service level expectations.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.workflow-sla'])
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Filters --}}
        <div class="sgh-card p-5">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="sgh-input sgh-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="sgh-input sgh-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">SLA Target (days)</label>
                    <input type="number" name="sla_days" value="{{ $slaDays }}" min="1" max="90" class="sgh-input sgh-input-sm w-28" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Type</label>
                    <select name="type" class="sgh-select sgh-select-sm">
                        <option value="">All</option>
                        <option value="{{ \App\Enums\Tenant\PaymentRequestType::Advance->value }}" @selected($type === \App\Enums\Tenant\PaymentRequestType::Advance->value)>Advance</option>
                        <option value="{{ \App\Enums\Tenant\PaymentRequestType::Expense->value }}" @selected($type === \App\Enums\Tenant\PaymentRequestType::Expense->value)>Expense</option>
                    </select>
                </div>
                <button type="submit" class="sgh-btn sgh-btn-primary sgh-btn-sm">Apply</button>
            </form>
        </div>

        {{-- KPI cards --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="sgh-card p-5">
                <div class="text-xs text-secondary-foreground mb-1">SLA Compliance Rate</div>
                <div class="text-3xl font-bold {{ $complianceRate >= 80 ? 'text-success' : ($complianceRate >= 60 ? 'text-warning' : 'text-danger') }}">
                    {{ $complianceRate }}%
                </div>
                <div class="text-xs text-secondary-foreground mt-1">within {{ $slaDays }}-day target</div>
            </div>
            <div class="sgh-card p-5">
                <div class="text-xs text-secondary-foreground mb-1">Average Approval Time</div>
                <div class="text-3xl font-bold text-mono">{{ $avgDays }}d</div>
                <div class="text-xs text-secondary-foreground mt-1">from submission to approval</div>
            </div>
            <div class="sgh-card p-5">
                <div class="text-xs text-secondary-foreground mb-1">Total Requests</div>
                <div class="text-3xl font-bold text-mono">{{ $total }}</div>
                <div class="text-xs text-secondary-foreground mt-1">
                    {{ $compliantCount }} compliant · {{ $total - $compliantCount }} over target
                </div>
            </div>
        </div>

        {{-- Detail table --}}
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Request Detail</h3>
            </div>

            @if($requests->isEmpty())
                <div class="sgh-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-shield-check-filled class="text-5xl text-muted-foreground mb-3" />
                    <p class="text-sm text-secondary-foreground">No approved requests in this period.</p>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">#</span></span></th>
                                    <th class="min-w-[150px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Staff</span></span></th>
                                    <th class="min-w-[80px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Type</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Submitted</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Approved</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Days Taken</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">SLA</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $row)
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $row['request']->id }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $row['request']->staff?->full_name ?? '—' }}</span></td>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm {{ $row['request']->type === 'advance' ? 'sgh-badge-primary' : 'sgh-badge-warning' }}">
                                                {{ ucfirst($row['request']->type) }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $row['request']->submitted_at?->format('M d, Y') ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $row['request']->approved_at?->format('M d, Y') ?? '—' }}</span></td>
                                        <td>
                                            <span class="text-sm font-semibold {{ $row['days'] > $slaDays ? 'text-danger' : 'text-success' }}">
                                                {{ $row['days'] }}d
                                            </span>
                                        </td>
                                        <td>
                                            @if($row['compliant'])
                                                <span class="sgh-badge sgh-badge-sm sgh-badge-success">On time</span>
                                            @else
                                                <span class="sgh-badge sgh-badge-sm sgh-badge-danger">Over target</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
