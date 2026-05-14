@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>Expenditure Summary</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Expenditure Summary</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Spend by department, branch, or account code over a selected period. Filter by request type (advance vs expense).
                The #1 report every finance team needs.
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
                    <label class="text-xs font-medium text-secondary-foreground">Group by</label>
                    <select name="group_by" class="kt-select kt-select-sm">
                        <option value="department" @selected($groupBy === 'department')>Department</option>
                        <option value="branch" @selected($groupBy === 'branch')>Branch</option>
                        <option value="account_code" @selected($groupBy === 'account_code')>Account Code</option>
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Type</label>
                    <select name="type" class="kt-select kt-select-sm">
                        <option value="">All</option>
                        <option value="advance" @selected($type === 'advance')>Advance</option>
                        <option value="expense" @selected($type === 'expense')>Expense</option>
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Results --}}
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Spend by {{ ucfirst(str_replace('_', ' ', $groupBy)) }}
                </h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">
                    {{ $dateFrom }} — {{ $dateTo }}
                </span>
            </div>

            @if($rows->isEmpty())
                <div class="kt-card-content flex flex-col items-center justify-center py-12">
                    <i class="ki-filled ki-chart-pie-simple text-5xl text-muted-foreground mb-3"></i>
                    <p class="text-sm text-secondary-foreground">No disbursed requests found for this period.</p>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ ucfirst(str_replace('_', ' ', $groupBy)) }}</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Requests</span></span></th>
                                    <th class="min-w-[150px]"><span class="kt-table-col"><span class="kt-table-col-label">Total Amount</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">% of Total</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td><span class="text-sm font-medium text-mono">{{ $row->label }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ number_format($row->count) }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ number_format((float) $row->total, 2) }}</span></td>
                                        <td>
                                            @php $pct = $grandTotal > 0 ? round(($row->total / $grandTotal) * 100, 1) : 0; @endphp
                                            <div class="flex items-center gap-2">
                                                <div class="h-1.5 w-24 rounded-full bg-muted overflow-hidden">
                                                    <div class="h-full bg-primary rounded-full" style="width: {{ $pct }}%"></div>
                                                </div>
                                                <span class="text-xs text-secondary-foreground">{{ $pct }}%</span>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-muted/30">
                                    <td class="text-sm font-semibold text-mono">Total</td>
                                    <td class="text-sm font-semibold">{{ number_format($rows->sum('count')) }}</td>
                                    <td class="text-sm font-semibold text-mono">{{ number_format((float) $grandTotal, 2) }}</td>
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
@endsection
