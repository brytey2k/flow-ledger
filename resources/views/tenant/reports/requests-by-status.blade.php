@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>Requests by Status</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Requests by Status</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Count and total value of all requests in each status — draft, in progress, approved, disbursed, and more.
                Filter by date range to see pipeline distribution for any period.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.requests-by-status'])
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
                <button type="submit" class="kt-btn kt-btn-sm kt-btn-primary">Apply</button>
                <a href="{{ route('reports.requests-by-status') }}" class="kt-btn kt-btn-sm kt-btn-light">Reset</a>
            </form>
        </div>

        @php
            $statusColors = [
                'draft'       => 'kt-badge-outline',
                'in_workflow' => 'kt-badge-primary',
                'approved'    => 'kt-badge-success',
                'disbursed'   => 'kt-badge-info',
                'retired'     => 'kt-badge-neutral',
                'sent_back'   => 'kt-badge-warning',
                'cancelled'   => 'kt-badge-danger',
                'denied'      => 'kt-badge-danger',
                'settled'     => 'kt-badge-success',
            ];
        @endphp

        <div class="grid gap-5 lg:grid-cols-2">

            {{-- Payment Requests --}}
            <div class="kt-card kt-card-grid">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Payment Requests</h3>
                    <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $paymentTotal }} total</span>
                </div>

                @if($paymentStatuses->isEmpty())
                    <div class="kt-card-content flex flex-col items-center justify-center py-8">
                        <p class="text-sm text-secondary-foreground">No payment requests found.</p>
                    </div>
                @else
                    <div class="kt-card-table">
                        <div class="kt-scrollable-x-auto border-b border-border">
                            <table class="kt-table kt-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">Status</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">Count</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">% of Total</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">Total Value</span></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentStatuses as $row)
                                        @php
                                            $pct = $paymentTotal > 0 ? round(($row->count / $paymentTotal) * 100, 1) : 0;
                                            $breakdownUrl = route('reports.breakdown', array_filter(['statuses' => $row->status, 'date_field' => 'created_at', 'date_from' => $dateFrom, 'date_to' => $dateTo, 'title' => 'Payment Requests — '.ucwords(str_replace('_', ' ', $row->status))]));
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="kt-badge kt-badge-sm {{ $statusColors[$row->status] ?? 'kt-badge-outline' }}">
                                                    {{ ucwords(str_replace('_', ' ', $row->status)) }}
                                                </span>
                                            </td>
                                            <td>
                                                <a href="{{ $breakdownUrl }}" class="text-sm font-semibold text-primary hover:underline">{{ number_format($row->count) }}</a>
                                            </td>
                                            <td>
                                                <span class="text-sm text-secondary-foreground">{{ $pct }}%</span>
                                            </td>
                                            <td><span class="text-sm font-medium text-mono">{{ number_format((float) $row->total, 2) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-muted/30">
                                        <td class="text-sm font-semibold">Total</td>
                                        <td class="text-sm font-semibold">{{ number_format($paymentTotal) }}</td>
                                        <td class="text-sm text-secondary-foreground">100%</td>
                                        <td class="text-sm font-semibold text-mono">{{ number_format((float) $paymentStatuses->sum('total'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Retirement Requests --}}
            <div class="kt-card kt-card-grid">
                <div class="kt-card-header">
                    <h3 class="kt-card-title">Retirement Requests</h3>
                    <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $retirementTotal }} total</span>
                </div>

                @if($retirementStatuses->isEmpty())
                    <div class="kt-card-content flex flex-col items-center justify-center py-8">
                        <p class="text-sm text-secondary-foreground">No retirement requests found.</p>
                    </div>
                @else
                    <div class="kt-card-table">
                        <div class="kt-scrollable-x-auto border-b border-border">
                            <table class="kt-table kt-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">Status</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">Count</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">% of Total</span></span></th>
                                        <th><span class="kt-table-col"><span class="kt-table-col-label">Total Value</span></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($retirementStatuses as $row)
                                        <tr>
                                            <td>
                                                <span class="kt-badge kt-badge-sm {{ $statusColors[$row->status] ?? 'kt-badge-outline' }}">
                                                    {{ ucwords(str_replace('_', ' ', $row->status)) }}
                                                </span>
                                            </td>
                                            <td><span class="text-sm font-semibold text-mono">{{ number_format($row->count) }}</span></td>
                                            <td>
                                                @php $pct = $retirementTotal > 0 ? round(($row->count / $retirementTotal) * 100, 1) : 0; @endphp
                                                <span class="text-sm text-secondary-foreground">{{ $pct }}%</span>
                                            </td>
                                            <td><span class="text-sm font-medium text-mono">{{ number_format((float) $row->total, 2) }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-muted/30">
                                        <td class="text-sm font-semibold">Total</td>
                                        <td class="text-sm font-semibold">{{ number_format($retirementTotal) }}</td>
                                        <td class="text-sm text-secondary-foreground">100%</td>
                                        <td class="text-sm font-semibold text-mono">{{ number_format((float) $retirementStatuses->sum('total'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>
</div>
@endsection
