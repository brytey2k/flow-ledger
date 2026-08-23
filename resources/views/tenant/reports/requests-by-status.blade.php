@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
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
                <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-primary">Apply</button>
                <a href="{{ route('reports.requests-by-status') }}" class="sgh-btn sgh-btn-sm sgh-btn-light">Reset</a>
            </form>
        </div>

        @php
            $statusColors = [
                'draft'       => 'sgh-badge-outline',
                'in_workflow' => 'sgh-badge-primary',
                'approved'    => 'sgh-badge-success',
                'disbursed'   => 'sgh-badge-info',
                'retired'     => 'sgh-badge-neutral',
                'sent_back'   => 'sgh-badge-warning',
                'cancelled'   => 'sgh-badge-danger',
                'denied'      => 'sgh-badge-danger',
                'settled'     => 'sgh-badge-success',
            ];
        @endphp

        <div class="grid gap-5 lg:grid-cols-2">

            {{-- Payment Requests --}}
            <div class="sgh-card sgh-card-grid">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title">Payment Requests</h3>
                    <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $paymentTotal }} total</span>
                </div>

                @if($paymentStatuses->isEmpty())
                    <div class="sgh-card-content flex flex-col items-center justify-center py-8">
                        <p class="text-sm text-secondary-foreground">No payment requests found.</p>
                    </div>
                @else
                    <div class="sgh-card-table">
                        <div class="sgh-scrollable-x-auto border-b border-border">
                            <table class="sgh-table sgh-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">Status</span></span></th>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">Count</span></span></th>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">% of Total</span></span></th>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">Total Value</span></span></th>
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
                                                <span class="sgh-badge sgh-badge-sm {{ $statusColors[$row->status] ?? 'sgh-badge-outline' }}">
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
            <div class="sgh-card sgh-card-grid">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title">Retirement Requests</h3>
                    <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $retirementTotal }} total</span>
                </div>

                @if($retirementStatuses->isEmpty())
                    <div class="sgh-card-content flex flex-col items-center justify-center py-8">
                        <p class="text-sm text-secondary-foreground">No retirement requests found.</p>
                    </div>
                @else
                    <div class="sgh-card-table">
                        <div class="sgh-scrollable-x-auto border-b border-border">
                            <table class="sgh-table sgh-table-border">
                                <thead>
                                    <tr>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">Status</span></span></th>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">Count</span></span></th>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">% of Total</span></span></th>
                                        <th><span class="sgh-table-col"><span class="sgh-table-col-label">Total Value</span></span></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($retirementStatuses as $row)
                                        <tr>
                                            <td>
                                                <span class="sgh-badge sgh-badge-sm {{ $statusColors[$row->status] ?? 'sgh-badge-outline' }}">
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
