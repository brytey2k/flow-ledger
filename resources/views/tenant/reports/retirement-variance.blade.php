@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Retirement Variance</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Retirement Variance</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Disbursed advance amount vs. amount actually expended in the approved retirement.
                Positive difference means a refund is owed; negative means the staff was paid extra.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.retirement-variance'])
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
                    <label class="text-xs font-medium text-secondary-foreground">Branch</label>
                    <select name="branch_id" class="sgh-select sgh-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" @selected((string) $branchId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-primary">Apply</button>
                <a href="{{ route('reports.retirement-variance') }}" class="sgh-btn sgh-btn-sm sgh-btn-light">Reset</a>
            </form>
        </div>

        {{-- Summary Cards --}}
        @if($rows->isNotEmpty())
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="sgh-card p-5">
                <div class="text-xs text-secondary-foreground mb-1">Total Disbursed</div>
                <div class="text-xl font-semibold text-mono">{{ number_format((float) $totalDisbursed, 2) }}</div>
            </div>
            <div class="sgh-card p-5">
                <div class="text-xs text-secondary-foreground mb-1">Total Expended</div>
                <div class="text-xl font-semibold text-mono">{{ number_format((float) $totalExpended, 2) }}</div>
            </div>
            <div class="sgh-card p-5">
                <div class="text-xs text-secondary-foreground mb-1">Net Difference</div>
                @php $net = $totalDisbursed - $totalExpended; @endphp
                <div class="text-xl font-semibold {{ $net > 0 ? 'text-success' : ($net < 0 ? 'text-danger' : 'text-mono') }}">
                    {{ $net >= 0 ? '+' : '' }}{{ number_format($net, 2) }}
                </div>
            </div>
        </div>
        @endif

        {{-- Table --}}
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Approved Retirements</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $rows->count() }} records</span>
            </div>

            @if($rows->isEmpty())
                <div class="sgh-card-content flex flex-col items-center justify-center py-12">
                    <p class="text-sm text-secondary-foreground">No approved retirements found for this period.</p>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th><span class="sgh-table-col"><span class="sgh-table-col-label">Request #</span></span></th>
                                    <th><span class="sgh-table-col"><span class="sgh-table-col-label">Staff</span></span></th>
                                    <th><span class="sgh-table-col"><span class="sgh-table-col-label">Branch</span></span></th>
                                    <th><span class="sgh-table-col sgh-table-col-end"><span class="sgh-table-col-label">Disbursed</span></span></th>
                                    <th><span class="sgh-table-col sgh-table-col-end"><span class="sgh-table-col-label">Expended</span></span></th>
                                    <th><span class="sgh-table-col sgh-table-col-end"><span class="sgh-table-col-label">Variance</span></span></th>
                                    <th><span class="sgh-table-col"><span class="sgh-table-col-label">Type</span></span></th>
                                    <th><span class="sgh-table-col"><span class="sgh-table-col-label">Approved</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    @php
                                        $variance = $row->disbursed_amount - $row->total_amount_expended;
                                        $differenceTypeLabels = [
                                            'refund_to_company' => 'Refund to Company',
                                            'pay_to_staff' => 'Pay to Staff',
                                            'nil' => 'Nil',
                                        ];
                                    @endphp
                                    <tr>
                                        <td><span class="text-sm font-medium text-mono">#{{ $row->payment_request_id }}</span></td>
                                        <td><span class="text-sm text-secondary-foreground">{{ $row->staff_name }}</span></td>
                                        <td><span class="text-sm text-secondary-foreground">{{ $row->branch_name }}</span></td>
                                        <td class="text-right"><span class="text-sm font-medium text-mono">{{ $row->currency_code }} {{ number_format((float) $row->disbursed_amount, 2) }}</span></td>
                                        <td class="text-right"><span class="text-sm font-medium text-mono">{{ $row->currency_code }} {{ number_format((float) $row->total_amount_expended, 2) }}</span></td>
                                        <td class="text-right">
                                            <span class="text-sm font-semibold {{ $variance > 0 ? 'text-success' : ($variance < 0 ? 'text-danger' : 'text-secondary-foreground') }}">
                                                {{ $variance >= 0 ? '+' : '' }}{{ number_format($variance, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm {{ $row->difference_type === 'refund_to_company' ? 'sgh-badge-success' : ($row->difference_type === 'pay_to_staff' ? 'sgh-badge-warning' : 'sgh-badge-outline') }}">
                                                {{ $differenceTypeLabels[$row->difference_type] ?? $row->difference_type }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm text-secondary-foreground">{{ \Carbon\Carbon::parse($row->approved_at)->format('M d, Y') }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-muted/30">
                                    <td colspan="3" class="text-sm font-semibold">Totals</td>
                                    <td class="text-right text-sm font-semibold text-mono">{{ number_format((float) $totalDisbursed, 2) }}</td>
                                    <td class="text-right text-sm font-semibold text-mono">{{ number_format((float) $totalExpended, 2) }}</td>
                                    <td class="text-right">
                                        @php $net = $totalDisbursed - $totalExpended; @endphp
                                        <span class="text-sm font-semibold {{ $net > 0 ? 'text-success' : ($net < 0 ? 'text-danger' : 'text-secondary-foreground') }}">
                                            {{ $net >= 0 ? '+' : '' }}{{ number_format($net, 2) }}
                                        </span>
                                    </td>
                                    <td colspan="2"></td>
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
