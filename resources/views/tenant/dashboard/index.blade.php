@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                {{ __('dashboard.title') }}
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('dashboard.welcome') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <span class="text-sm text-muted-foreground">{{ now()->format('M d, Y') }}</span>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="grid gap-5 lg:grid-cols-3 lg:gap-7.5">
            <div class="kt-card">
                <div class="kt-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('dashboard.accounts') }}</span>
                        <i class="ki-filled ki-wallet text-lg text-primary"></i>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold">—</span>
                        <span class="text-sm text-muted-foreground">{{ __('dashboard.no_accounts') }}</span>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('dashboard.transactions') }}</span>
                        <i class="ki-filled ki-arrows-loop text-lg text-blue-500"></i>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold">—</span>
                        <span class="text-sm text-muted-foreground">{{ __('dashboard.no_transactions') }}</span>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('dashboard.reports') }}</span>
                        <i class="ki-filled ki-chart-line-up text-lg text-green-500"></i>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold">—</span>
                        <span class="text-sm text-muted-foreground">{{ __('dashboard.no_data') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="kt-card">
            <div class="kt-card-content flex flex-col items-center justify-center gap-4 py-16">
                <div class="flex size-16 items-center justify-center rounded-full bg-primary/10">
                    <i class="ki-filled ki-book-open text-3xl text-primary"></i>
                </div>
                <div class="flex flex-col items-center gap-2 text-center">
                    <h3 class="text-lg font-semibold">{{ __('dashboard.ledger_ready') }}</h3>
                    <p class="text-sm text-muted-foreground max-w-sm">
                        {{ __('dashboard.ledger_hint') }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
