@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>Denied &amp; Cancelled Analysis</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Denied &amp; Cancelled Analysis</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Count and value of denied and cancelled requests by branch and type.
                High failure rates in a branch or type signal a process or training gap worth investigating.
            </p>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Filters --}}
        <div class="kt-card p-5">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="kt-input kt-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="kt-input kt-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Branch</label>
                    <select name="branch_id" class="kt-select kt-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" @selected((string) $branchId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Type</label>
                    <select name="type" class="kt-select kt-select-sm">
                        <option value="">All Types</option>
                        <option value="advance" @selected($type === 'advance')>Advance</option>
                        <option value="expense" @selected($type === 'expense')>Expense</option>
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">Apply</button>
                <a href="{{ route('reports.denied-cancelled') }}" class="kt-btn kt-btn-sm kt-btn-light">Reset</a>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="kt-card p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-danger/10">
                        <i class="ki-filled ki-cross text-sm text-danger"></i>
                    </div>
                    <div class="text-xs text-secondary-foreground">Denied</div>
                </div>
                <div class="text-2xl font-semibold text-mono">{{ number_format($deniedCount) }}</div>
                <div class="text-xs text-secondary-foreground mt-1">requests denied in period</div>
            </div>
            <div class="kt-card p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-warning/10">
                        <i class="ki-filled ki-minus-circle text-sm text-warning"></i>
                    </div>
                    <div class="text-xs text-secondary-foreground">Cancelled</div>
                </div>
                <div class="text-2xl font-semibold text-mono">{{ number_format($cancelledCount) }}</div>
                <div class="text-xs text-secondary-foreground mt-1">requests cancelled in period</div>
            </div>
        </div>

        {{-- Breakdown Table --}}
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Breakdown by Branch &amp; Type</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $rows->count() }} groups</span>
            </div>

            @if($rows->isEmpty())
                <div class="kt-card-content flex flex-col items-center justify-center py-12">
                    <p class="text-sm text-secondary-foreground">No denied or cancelled requests found for this period.</p>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th><span class="kt-table-col"><span class="kt-table-col-label">Branch</span></span></th>
                                    <th><span class="kt-table-col"><span class="kt-table-col-label">Type</span></span></th>
                                    <th><span class="kt-table-col"><span class="kt-table-col-label">Status</span></span></th>
                                    <th><span class="kt-table-col kt-table-col-end"><span class="kt-table-col-label">Count</span></span></th>
                                    <th><span class="kt-table-col kt-table-col-end"><span class="kt-table-col-label">Total Value</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byBranch as $branchName => $branchRows)
                                    @foreach($branchRows as $i => $row)
                                        @php
                                            $breakdownUrl = route('reports.breakdown', array_filter(['statuses' => $row->status, 'date_field' => 'updated_at', 'branch_id' => $row->branch_id, 'type' => $row->type, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'title' => $branchName.' — '.ucfirst($row->type).' '.ucfirst($row->status)]));
                                        @endphp
                                        <tr>
                                            @if($i === 0)
                                                <td rowspan="{{ $branchRows->count() }}" class="align-top">
                                                    <span class="text-sm font-medium">{{ $branchName }}</span>
                                                </td>
                                            @endif
                                            <td>
                                                <span class="kt-badge kt-badge-sm kt-badge-outline">{{ ucfirst($row->type) }}</span>
                                            </td>
                                            <td>
                                                <span class="kt-badge kt-badge-sm {{ $row->status === 'denied' ? 'kt-badge-danger' : 'kt-badge-warning' }}">
                                                    {{ ucfirst($row->status) }}
                                                </span>
                                            </td>
                                            <td class="text-right">
                                                <a href="{{ $breakdownUrl }}" class="text-sm font-semibold text-primary hover:underline">{{ number_format($row->count) }}</a>
                                            </td>
                                            <td class="text-right"><span class="text-sm font-medium text-mono">{{ number_format((float) $row->total, 2) }}</span></td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-muted/30">
                                    <td colspan="3" class="text-sm font-semibold">Total</td>
                                    <td class="text-right text-sm font-semibold text-mono">{{ number_format($rows->sum('count')) }}</td>
                                    <td class="text-right text-sm font-semibold text-mono">{{ number_format((float) $rows->sum('total'), 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
