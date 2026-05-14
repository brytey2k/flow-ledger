@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>Cash Position</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Cash Position</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Current balance per cashbook with period receipts and payments. Shows net cash movement for any date range
                and gives a clear picture of available funds across all branches.
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
                    <label class="text-xs font-medium text-secondary-foreground">Period From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="kt-input kt-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Period To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="kt-input kt-input-sm" />
                </div>
                <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Cashbook cards --}}
        @if($cashbooks->isEmpty())
            <div class="kt-card kt-card-content flex flex-col items-center justify-center py-12">
                <i class="ki-filled ki-calculator text-5xl text-muted-foreground mb-3"></i>
                <p class="text-sm text-secondary-foreground">No cashbooks found.</p>
            </div>
        @else
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($cashbooks as $row)
                    <div class="kt-card p-5 flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold text-mono">{{ $row['cashbook']->branch?->name ?? 'Unknown Branch' }}</div>
                                <div class="text-xs text-secondary-foreground">{{ $row['cashbook']->currency?->code ?? '' }} Cashbook</div>
                            </div>
                            <i class="ki-filled ki-calculator text-2xl text-muted-foreground"></i>
                        </div>

                        <div class="flex flex-col gap-1">
                            <div class="text-xs text-secondary-foreground">Current Balance</div>
                            <div class="text-2xl font-bold text-mono">
                                {{ $row['cashbook']->currency?->symbol ?? '' }} {{ number_format((float) $row['current_balance'], 2) }}
                            </div>
                        </div>

                        <div class="border-t border-border pt-3 grid grid-cols-2 gap-3">
                            <div>
                                <div class="text-xs text-secondary-foreground mb-0.5">Period Receipts</div>
                                <div class="text-sm font-semibold text-success">
                                    + {{ number_format((float) $row['period_credits'], 2) }}
                                </div>
                            </div>
                            <div>
                                <div class="text-xs text-secondary-foreground mb-0.5">Period Payments</div>
                                <div class="text-sm font-semibold text-danger">
                                    − {{ number_format((float) $row['period_debits'], 2) }}
                                </div>
                            </div>
                        </div>

                        <div class="text-xs text-secondary-foreground">
                            {{ $row['entry_count'] }} {{ Str::plural('entry', $row['entry_count']) }} in period
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>
@endsection
