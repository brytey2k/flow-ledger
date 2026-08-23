@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Cash Count Report</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Cash Count Report</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                All physical cash counts for a date range. Shows counted totals against book balances, highlights discrepancies, and identifies who performed each count.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.cash-count'])
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

        {{-- Summary Stats --}}
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="fl-card p-5">
                <p class="text-xs text-secondary-foreground mb-1">Total Counts</p>
                <p class="text-2xl font-semibold text-mono">{{ $totalCounts }}</p>
            </div>
            <div class="fl-card p-5">
                <p class="text-xs text-secondary-foreground mb-1">With Discrepancy</p>
                <p class="text-2xl font-semibold {{ $withDiscrepancy > 0 ? 'text-warning' : 'text-success' }}">{{ $withDiscrepancy }}</p>
            </div>
            <div class="fl-card p-5">
                <p class="text-xs text-secondary-foreground mb-1">Net Difference</p>
                @php $diffClass = $netDifference > 0.01 ? 'text-success' : ($netDifference < -0.01 ? 'text-danger' : 'text-foreground'); @endphp
                <p class="text-2xl font-semibold {{ $diffClass }}">{{ number_format($netDifference, 2) }}</p>
            </div>
        </div>

        {{-- Table --}}
        <div class="fl-card fl-card-grid">
            <div class="fl-card-header">
                <h3 class="fl-card-title">Cash Counts</h3>
                <span class="fl-badge fl-badge-sm fl-badge-outline">{{ $totalCounts }} records</span>
            </div>

            @if($rows->isEmpty())
                <div class="fl-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-calculator-filled class="text-5xl text-muted-foreground mb-3" />
                    <p class="text-sm text-secondary-foreground">No cash counts found for this period.</p>
                </div>
            @else
                <div class="fl-card-table">
                    <div class="fl-scrollable-x-auto border-b border-border">
                        <table class="fl-table fl-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[130px]"><span class="fl-table-col"><span class="fl-table-col-label">Branch</span></span></th>
                                    <th class="min-w-[100px]"><span class="fl-table-col"><span class="fl-table-col-label">Currency</span></span></th>
                                    <th class="min-w-[150px]"><span class="fl-table-col"><span class="fl-table-col-label">Counted At</span></span></th>
                                    <th class="min-w-[140px]"><span class="fl-table-col"><span class="fl-table-col-label">Counted By</span></span></th>
                                    <th class="min-w-[130px]"><span class="fl-table-col"><span class="fl-table-col-label">Book Balance</span></span></th>
                                    <th class="min-w-[130px]"><span class="fl-table-col"><span class="fl-table-col-label">Counted Total</span></span></th>
                                    <th class="min-w-[120px]"><span class="fl-table-col"><span class="fl-table-col-label">Difference</span></span></th>
                                    <th class="min-w-[100px]"><span class="fl-table-col"><span class="fl-table-col-label">Status</span></span></th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $count)
                                    @php
                                        $symbol = $count->cashbook?->currency?->symbol ?? '';
                                        $status = $count->status();
                                        $statusClass = match($status) {
                                            'surplus'  => 'fl-badge-success',
                                            'deficit'  => 'fl-badge-danger',
                                            default    => 'fl-badge-neutral',
                                        };
                                        $diffSign = (float) $count->difference > 0.01 ? '+' : '';
                                    @endphp
                                    <tr>
                                        <td><span class="text-sm font-medium text-mono">{{ $count->cashbook?->branch?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $count->cashbook?->currency?->code ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $count->counted_at->format('d M Y, H:i') }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $count->countedBy?->full_name ?? '—' }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $symbol }} {{ number_format((float) $count->cashbook_balance_at_count, 2) }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $symbol }} {{ number_format((float) $count->counted_total, 2) }}</span></td>
                                        <td>
                                            <span class="text-sm font-medium {{ (float) $count->difference > 0.01 ? 'text-success' : ((float) $count->difference < -0.01 ? 'text-danger' : 'text-foreground') }}">
                                                {{ $diffSign }}{{ number_format((float) $count->difference, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="fl-badge fl-badge-sm {{ $statusClass }}">{{ ucfirst($status) }}</span>
                                        </td>
                                        <td>
                                            @if($count->cashbook?->branch)
                                                <a href="{{ route('cash-count.show', [$count->cashbook->branch, $count]) }}" class="fl-btn fl-btn-xs fl-btn-light">
                                                    Details <x-tabler-arrow-right class="text-xs" />
                                                </a>
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
