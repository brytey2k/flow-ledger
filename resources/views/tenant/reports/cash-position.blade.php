@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Cash Position</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Cash Position</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Current cash per branch, approved payments awaiting release, and the amount still free for new commitments.
                Period receipts and payments show movement within the selected date range.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.cash-position'])
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Filters --}}
        <div class="sgh-card p-5">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Period From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="sgh-input sgh-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Period To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="sgh-input sgh-input-sm" />
                </div>
                <button type="submit" class="sgh-btn sgh-btn-primary sgh-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Cashbook cards --}}
        @if($cashbooks->isEmpty())
            <div class="sgh-card sgh-card-content flex flex-col items-center justify-center py-12">
                <x-tabler-calculator-filled class="text-5xl text-muted-foreground mb-3" />
                <p class="text-sm text-secondary-foreground">No cashbooks found.</p>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($cashbooks as $row)
                    <div class="sgh-card p-5 flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-mono">{{ $row['cashbook']->branch?->name ?? 'Unknown Branch' }}</div>
                                <div class="text-xs text-secondary-foreground">{{ $row['cashbook']->currency?->code ?? '' }} Cashbook</div>
                            </div>
                            <x-tabler-calculator-filled class="text-2xl text-muted-foreground" />
                        </div>

                        <div class="flex flex-col gap-1">
                            <div class="text-xs text-secondary-foreground">Current Balance</div>
                            <div class="text-2xl font-bold text-mono">
                                {{ $row['cashbook']->currency?->symbol ?? '' }} {{ number_format((float) $row['current_balance'], 2) }}
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-3 rounded-lg bg-muted/40 p-3">
                            <div>
                                <div class="text-xs text-secondary-foreground mb-0.5">Approved Awaiting Release</div>
                                <div class="text-sm font-semibold text-warning">
                                    {{ number_format((float) $row['approved_awaiting_release'], 2) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-secondary-foreground mb-0.5">Available Uncommitted Cash</div>
                                <div class="text-sm font-semibold {{ $row['available_uncommitted_cash'] < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ $row['cashbook']->currency?->symbol ?? '' }} {{ number_format((float) $row['available_uncommitted_cash'], 2) }}
                                </div>
                            </div>
                        </div>

                        <div class="border-t border-border pt-3 grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-xs text-secondary-foreground mb-0.5">Period Receipts</div>
                                <div class="text-sm font-semibold text-success">
                                    + {{ number_format((float) $row['period_receipts'], 2) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-secondary-foreground mb-0.5">Period Payments</div>
                                <div class="text-sm font-semibold text-danger">
                                    − {{ number_format((float) $row['period_payments'], 2) }}
                                </div>
                            </div>
                        </div>

                        <a href="{{ route('cashbook.index', $row['cashbook']->branch) }}?date_from={{ $dateFrom }}&date_to={{ $dateTo }}" class="text-xs text-primary hover:underline">
                            {{ $row['entry_count'] }} {{ Str::plural('entry', $row['entry_count']) }} in period →
                        </a>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection
