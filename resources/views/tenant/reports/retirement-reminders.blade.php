@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Retirement Reminders</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Retirement Reminders</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Disbursed advances that have triggered overdue retirement reminders — how many reminders were sent per advance and when the last one was dispatched.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.retirement-reminders'])
    </div>
</div>

<div class="fl-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Filters --}}
        <div class="fl-card p-5">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="fl-input fl-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="fl-input fl-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Branch</label>
                    <select name="branch_id" class="fl-select fl-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" @selected((string) $branchId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="fl-btn fl-btn-primary fl-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Summary card --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="fl-card p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-secondary-foreground">Advances with Reminders</span>
                    <span class="fl-badge fl-badge-sm fl-badge-warning">Overdue</span>
                </div>
                <div class="text-2xl font-semibold text-mono">{{ $rows->count() }}</div>
                <div class="text-xs text-secondary-foreground mt-1">In selected period</div>
            </div>
            <div class="fl-card p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-secondary-foreground">Total Reminders Sent</span>
                    <span class="fl-badge fl-badge-sm fl-badge-outline">Count</span>
                </div>
                <div class="text-2xl font-semibold text-mono">{{ number_format($rows->sum('reminder_count')) }}</div>
                <div class="text-xs text-secondary-foreground mt-1">Across all advances</div>
            </div>
            <div class="fl-card p-5">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-secondary-foreground">Total Outstanding</span>
                    <span class="fl-badge fl-badge-sm fl-badge-danger">Amount</span>
                </div>
                <div class="text-2xl font-semibold text-mono">{{ number_format((float) $rows->sum('total_amount'), 2) }}</div>
                <div class="text-xs text-secondary-foreground mt-1">Sum of advance amounts</div>
            </div>
        </div>

        {{-- Detail table --}}
        <div class="fl-card fl-card-grid">
            <div class="fl-card-header">
                <h3 class="fl-card-title">Retirement Reminder Log</h3>
                <span class="fl-badge fl-badge-sm fl-badge-outline">{{ $rows->count() }} advances</span>
            </div>

            @if($rows->isEmpty())
                <div class="fl-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-shield-check-filled class="text-5xl text-success mb-3" />
                    <p class="text-sm font-medium text-foreground">No reminders sent in this period.</p>
                    <p class="text-xs text-secondary-foreground mt-1">All advances may be within the grace period or already retired.</p>
                </div>
            @else
                <div class="fl-card-table">
                    <div class="fl-scrollable-x-auto border-b border-border">
                        <table class="fl-table fl-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[160px]"><span class="fl-table-col"><span class="fl-table-col-label">Staff</span></span></th>
                                    <th class="min-w-[130px]"><span class="fl-table-col"><span class="fl-table-col-label">Branch</span></span></th>
                                    <th class="min-w-[120px]"><span class="fl-table-col"><span class="fl-table-col-label">Amount</span></span></th>
                                    <th class="min-w-[120px]"><span class="fl-table-col"><span class="fl-table-col-label">Disbursed On</span></span></th>
                                    <th class="min-w-[110px]"><span class="fl-table-col"><span class="fl-table-col-label">Days Overdue</span></span></th>
                                    <th class="min-w-[130px]"><span class="fl-table-col"><span class="fl-table-col-label">Reminders Sent</span></span></th>
                                    <th class="min-w-[130px]"><span class="fl-table-col"><span class="fl-table-col-label">Last Reminder</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    @php
                                        $daysOverdue = $row->disbursed_at
                                            ? (int) now()->diffInDays(\Carbon\Carbon::parse($row->disbursed_at))
                                            : 0;
                                        $urgencyColor = match(true) {
                                            $daysOverdue > 60 => 'fl-badge-danger',
                                            $daysOverdue > 30 => 'fl-badge-warning',
                                            default => 'fl-badge-outline',
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="text-sm font-medium text-mono">{{ $row->staff_name }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $row->branch_name }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ number_format((float) $row->total_amount, 2) }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $row->disbursed_at ? \Carbon\Carbon::parse($row->disbursed_at)->format('M d, Y') : '—' }}</span></td>
                                        <td>
                                            <span class="fl-badge fl-badge-sm {{ $urgencyColor }}">{{ $daysOverdue }} days</span>
                                        </td>
                                        <td>
                                            <span class="fl-badge fl-badge-sm fl-badge-primary">{{ $row->reminder_count }}</span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $row->last_reminder_date ? \Carbon\Carbon::parse($row->last_reminder_date)->format('M d, Y') : '—' }}</span></td>
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
