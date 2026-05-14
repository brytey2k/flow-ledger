@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>Outstanding Advances</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Outstanding Advances</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Staff with disbursed advances that have no approved retirement. Includes aging buckets (0–30, 31–60, 61+ days).
                Compliance-critical — unretired advances represent unaccounted money.
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
                    <label class="text-xs font-medium text-secondary-foreground">Branch</label>
                    <select name="branch_id" class="kt-select kt-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" @selected((string) $branchId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Aging summary cards --}}
        @php
            $agingLabels = ['0–30 days', '31–60 days', '61+ days'];
            $agingColors = ['kt-badge-success', 'kt-badge-warning', 'kt-badge-danger'];
        @endphp
        <div class="grid gap-4 sm:grid-cols-3">
            @foreach($agingLabels as $i => $label)
                <div class="kt-card p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-secondary-foreground">{{ $label }}</span>
                        <span class="kt-badge kt-badge-sm {{ $agingColors[$i] }}">Aging</span>
                    </div>
                    <div class="text-2xl font-semibold text-mono">
                        {{ $buckets->get($label)?->count() ?? 0 }}
                    </div>
                    <div class="text-xs text-secondary-foreground mt-1">
                        Total: {{ number_format($buckets->get($label)?->sum(fn($r) => $r['request']->total_amount) ?? 0, 2) }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Detail table --}}
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Outstanding Advances</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $advances->count() }} advances</span>
            </div>

            @if($advances->isEmpty())
                <div class="kt-card-content flex flex-col items-center justify-center py-12">
                    <i class="ki-filled ki-shield-tick text-5xl text-success mb-3"></i>
                    <p class="text-sm font-medium text-foreground">All advances have been retired.</p>
                    <p class="text-xs text-secondary-foreground mt-1">No outstanding advances found.</p>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="kt-table-col"><span class="kt-table-col-label">#</span></span></th>
                                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Staff</span></span></th>
                                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">Branch</span></span></th>
                                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">Department</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Amount</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Disbursed On</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Days Outstanding</span></span></th>
                                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Aging</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($advances as $row)
                                    @php
                                        $req = $row['request'];
                                        $bucketColor = match($row['bucket']) {
                                            '0–30 days' => 'kt-badge-success',
                                            '31–60 days' => 'kt-badge-warning',
                                            default => 'kt-badge-danger',
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $req->id }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $req->staff?->full_name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $req->branch?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $req->staff?->department?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $req->currency?->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $req->disbursed_at?->format('M d, Y') ?? '—' }}</span></td>
                                        <td><span class="text-sm font-semibold text-mono">{{ $row['days'] }}</span></td>
                                        <td><span class="kt-badge kt-badge-sm {{ $bucketColor }}">{{ $row['bucket'] }}</span></td>
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
