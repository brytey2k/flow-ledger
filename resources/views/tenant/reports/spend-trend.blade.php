@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>Spend Trend</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Spend Trend</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Total disbursed spend per month for a selected year. Reveals seasonal patterns, budget burn rate,
                and year-over-year changes in organisational expenditure.
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
                    <label class="text-xs font-medium text-secondary-foreground">Year</label>
                    <select name="year" class="kt-select kt-select-sm">
                        @foreach($years as $yr)
                            <option value="{{ $yr }}" @selected($yr === $year)>{{ $yr }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Type</label>
                    <select name="type" class="kt-select kt-select-sm">
                        <option value="">All</option>
                        <option value="{{ \App\Enums\Tenant\PaymentRequestType::Advance->value }}" @selected($type === \App\Enums\Tenant\PaymentRequestType::Advance->value)>Advance</option>
                        <option value="{{ \App\Enums\Tenant\PaymentRequestType::Expense->value }}" @selected($type === \App\Enums\Tenant\PaymentRequestType::Expense->value)>Expense</option>
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Apply</button>
            </form>
        </div>

        @if(!$rows->isEmpty())
        {{-- Chart --}}
        @php
            $chartLabels = $rows->pluck('month_label')->toArray();
            $chartTotals = $rows->map(fn($r) => round((float) $r->total, 2))->toArray();
            $chartCounts = $rows->pluck('count')->toArray();
            $chartDrillUrls = $rows->map(function ($row) use ($year) {
                $monthNum = (int) $row->month_num;
                $mFrom = $year.'-'.str_pad($monthNum, 2, '0', STR_PAD_LEFT).'-01';
                $mTo   = $year.'-'.str_pad($monthNum, 2, '0', STR_PAD_LEFT).'-'.date('t', mktime(0,0,0,$monthNum,1,$year));
                return route('reports.disbursement-register', ['date_from' => $mFrom, 'date_to' => $mTo]);
            })->toArray();
        @endphp
        <div class="kt-card p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-mono">Monthly Spend — {{ $year }}</h3>
                <span class="text-xs text-secondary-foreground">Click a bar to drill into that month</span>
            </div>
            <div id="spend_trend_chart"></div>
        </div>
        @endif

        {{-- Monthly table --}}
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Monthly Spend — {{ $year }}</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $rows->count() }} months with data</span>
            </div>

            @if($rows->isEmpty())
                <div class="kt-card-content flex flex-col items-center justify-center py-12">
                    <i class="ki-filled ki-chart-line-up text-5xl text-muted-foreground mb-3"></i>
                    <p class="text-sm text-secondary-foreground">No disbursed requests found for {{ $year }}.</p>
                </div>
            @else
                @php $maxTotal = $rows->max('total') ?: 1; @endphp
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Month</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Requests</span></span></th>
                                    <th class="min-w-[150px]"><span class="kt-table-col"><span class="kt-table-col-label">Total Disbursed</span></span></th>
                                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">Volume</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    @php
                                        $barPct = $maxTotal > 0 ? round(($row->total / $maxTotal) * 100) : 0;
                                        $monthNum = (int) $row->month_num;
                                        $mFrom = $year.'-'.str_pad($monthNum, 2, '0', STR_PAD_LEFT).'-01';
                                        $mTo = $year.'-'.str_pad($monthNum, 2, '0', STR_PAD_LEFT).'-'.date('t', mktime(0, 0, 0, $monthNum, 1, $year));
                                        $drillUrl = route('reports.disbursement-register', ['date_from' => $mFrom, 'date_to' => $mTo]);
                                    @endphp
                                    <tr>
                                        <td>
                                            <a href="{{ $drillUrl }}" class="text-sm font-medium text-mono hover:text-primary hover:underline">{{ $row->month_label }} {{ $year }}</a>
                                        </td>
                                        <td>
                                            <a href="{{ $drillUrl }}" class="text-sm font-semibold text-primary hover:underline">{{ number_format($row->count) }}</a>
                                        </td>
                                        <td><span class="text-sm font-semibold text-mono">{{ number_format((float) $row->total, 2) }}</span></td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="h-2 w-40 rounded-full bg-muted overflow-hidden">
                                                    <div class="h-full bg-primary rounded-full" style="width: {{ $barPct }}%"></div>
                                                </div>
                                                <span class="text-xs text-secondary-foreground">{{ $barPct }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-muted/30">
                                    <td class="text-sm font-semibold">Year Total</td>
                                    <td class="text-sm font-semibold">{{ number_format($rows->sum('count')) }}</td>
                                    <td class="text-sm font-semibold text-mono">{{ number_format((float) $rows->sum('total'), 2) }}</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>

@if(!$rows->isEmpty())
@push('page_js')
<script>
(function () {
    var labels   = @json($chartLabels);
    var totals   = @json($chartTotals);
    var counts   = @json($chartCounts);
    var drillUrls = @json($chartDrillUrls);

    var options = {
        series: [{ name: 'Total Disbursed', data: totals }],
        chart: {
            type: 'bar',
            height: 280,
            toolbar: { show: false },
            events: {
                dataPointSelection: function (event, chartContext, config) {
                    var url = drillUrls[config.dataPointIndex];
                    if (url) window.location.href = url;
                }
            }
        },
        plotOptions: {
            bar: { borderRadius: 4, columnWidth: '55%', cursor: 'pointer' }
        },
        dataLabels: { enabled: false },
        colors: ['var(--color-primary)'],
        xaxis: {
            categories: labels,
            axisBorder: { show: false },
            axisTicks: { show: false },
            labels: { style: { colors: 'var(--color-secondary-foreground)', fontSize: '12px' } }
        },
        yaxis: {
            axisTicks: { show: false },
            labels: {
                style: { colors: 'var(--color-secondary-foreground)', fontSize: '12px' },
                formatter: function (val) {
                    if (val >= 1000000) return (val / 1000000).toFixed(1) + 'M';
                    if (val >= 1000) return (val / 1000).toFixed(0) + 'K';
                    return val;
                }
            }
        },
        tooltip: {
            custom: function (opts) {
                var i = opts.dataPointIndex;
                var total = new Intl.NumberFormat().format(totals[i]);
                var count = counts[i];
                return '<div class="flex flex-col gap-1 p-3">' +
                    '<div class="text-xs font-medium">' + labels[i] + '</div>' +
                    '<div class="text-sm font-semibold">' + total + '</div>' +
                    '<div class="text-xs text-secondary-foreground">' + count + ' requests</div>' +
                    '</div>';
            }
        },
        grid: {
            borderColor: 'var(--color-border)',
            strokeDashArray: 4,
            yaxis: { lines: { show: true } },
            xaxis: { lines: { show: false } }
        },
        states: {
            hover: { filter: { type: 'darken', value: 0.85 } }
        }
    };

    var el = document.querySelector('#spend_trend_chart');
    if (el && typeof ApexCharts !== 'undefined') {
        new ApexCharts(el, options).render();
    }
})();
</script>
@endpush

@endif

@endsection
