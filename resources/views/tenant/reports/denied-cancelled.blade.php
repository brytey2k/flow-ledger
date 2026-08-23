@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Denied &amp; Cancelled Analysis</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Denied &amp; Cancelled Analysis</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Count and value of denied and cancelled requests by branch and type.
                High failure rates in a branch or type signal a process or training gap worth investigating.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.denied-cancelled'])
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
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Type</label>
                    <select name="type" class="fl-select fl-select-sm">
                        <option value="">All Types</option>
                        <option value="advance" @selected($type === 'advance')>Advance</option>
                        <option value="expense" @selected($type === 'expense')>Expense</option>
                    </select>
                </div>
                <button type="submit" class="fl-btn fl-btn-sm fl-btn-primary">Apply</button>
                <a href="{{ route('reports.denied-cancelled') }}" class="fl-btn fl-btn-sm fl-btn-light">Reset</a>
            </form>
        </div>

        {{-- Summary Cards --}}
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="fl-card p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-danger/10">
                        <x-tabler-x-filled class="text-sm text-danger" />
                    </div>
                    <div class="text-xs text-secondary-foreground">Denied</div>
                </div>
                <div class="text-2xl font-semibold text-mono">{{ number_format($deniedCount) }}</div>
                <div class="text-xs text-secondary-foreground mt-1">requests denied in period</div>
            </div>
            <div class="fl-card p-5">
                <div class="flex items-center gap-3 mb-2">
                    <div class="flex size-8 items-center justify-center rounded-lg bg-warning/10">
                        <x-tabler-circle-minus class="text-sm text-warning" />
                    </div>
                    <div class="text-xs text-secondary-foreground">Cancelled</div>
                </div>
                <div class="text-2xl font-semibold text-mono">{{ number_format($cancelledCount) }}</div>
                <div class="text-xs text-secondary-foreground mt-1">requests cancelled in period</div>
            </div>
        </div>

        {{-- Breakdown Table --}}
        <div class="fl-card fl-card-grid">
            <div class="fl-card-header">
                <h3 class="fl-card-title">Breakdown by Branch &amp; Type</h3>
                <span class="fl-badge fl-badge-sm fl-badge-outline">{{ $rows->count() }} groups</span>
            </div>

            @if($rows->isEmpty())
                <div class="fl-card-content flex flex-col items-center justify-center py-12">
                    <p class="text-sm text-secondary-foreground">No denied or cancelled requests found for this period.</p>
                </div>
            @else
                <div class="fl-card-table">
                    <div class="fl-scrollable-x-auto border-b border-border">
                        <table class="fl-table fl-table-border">
                            <thead>
                                <tr>
                                    <th><span class="fl-table-col"><span class="fl-table-col-label">Branch</span></span></th>
                                    <th><span class="fl-table-col"><span class="fl-table-col-label">Type</span></span></th>
                                    <th><span class="fl-table-col"><span class="fl-table-col-label">Status</span></span></th>
                                    <th><span class="fl-table-col fl-table-col-end"><span class="fl-table-col-label">Count</span></span></th>
                                    <th><span class="fl-table-col fl-table-col-end"><span class="fl-table-col-label">Total Value</span></span></th>
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
                                                <span class="fl-badge fl-badge-sm fl-badge-outline">{{ ucfirst($row->type) }}</span>
                                            </td>
                                            <td>
                                                <span class="fl-badge fl-badge-sm {{ $row->status === 'denied' ? 'fl-badge-danger' : 'fl-badge-warning' }}">
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
