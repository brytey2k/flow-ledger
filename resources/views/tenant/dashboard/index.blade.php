@extends('tenant.layouts.base')

@section('content')
@php
    $summary = $dashboard['summary'] ?? [];
    $personal = $dashboard['personal'] ?? [];
    $pipeline = $dashboard['pipeline'] ?? [];
    $trends = $dashboard['trends'] ?? [];
    $insights = $dashboard['insights'] ?? [];
    $links = $dashboard['links'] ?? [];
@endphp
<div class="sgh-container-fixed">
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

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        @if(!empty($lowCashBranches))
            <div class="sgh-card">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title">
                        <x-tabler-alert-triangle-filled class="text-warning mr-2" />
                        {{ __('cash_balance.alert_widget_title') }}
                    </h3>
                </div>
                <div class="sgh-card-content p-5 lg:p-7.5">
                    <div class="grid gap-3">
                        @foreach($lowCashBranches as $branch)
                            <div class="flex items-center justify-between rounded-lg border border-warning/30 bg-warning/10 p-4">
                                <div>
                                    <div class="font-medium text-foreground">{{ $branch['name'] }}</div>
                                    <div class="text-sm text-muted-foreground">
                                        {{ __('cash_balance.current_balance') }}:
                                        <span class="font-medium text-warning">
                                            {{ $branch['currency_symbol'] }} {{ number_format((float) $branch['balance'], 2) }}
                                        </span>
                                        ·
                                        {{ __('cash_balance.threshold') }}:
                                        <span class="font-medium">
                                            {{ $branch['currency_symbol'] }} {{ number_format((float) $branch['threshold'], 2) }}
                                        </span>
                                    </div>
                                </div>
                                <a href="{{ $links['cash_thresholds'] ?? route('cash-balance-thresholds.index') }}" class="sgh-btn sgh-btn-sm sgh-btn-ghost text-primary">
                                    <x-tabler-arrow-right />
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        <div class="flex flex-col gap-5 md:flex-row lg:gap-7.5">
            <div class="sgh-card md:flex-1">
                <div class="sgh-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('dashboard.pending_approvals') }}</span>
                        <x-tabler-circle-check-filled class="text-lg text-primary" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold">{{ $summary['pending_approvals'] ?? 0 }}</span>
                        <a href="{{ $links['approvals'] ?? route('approvals.index') }}" class="text-sm text-primary hover:underline">
                            {{ __('dashboard.review_queue') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="sgh-card md:flex-1">
                <div class="sgh-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('dashboard.pending_disbursements') }}</span>
                        <x-tabler-currency-dollar class="text-lg text-blue-500" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold">{{ $summary['pending_disbursements'] ?? 0 }}</span>
                        <a href="{{ $links['disbursements'] ?? route('disbursements.index') }}" class="text-sm text-primary hover:underline">
                            {{ __('dashboard.take_action') }}
                        </a>
                    </div>
                </div>
            </div>

            <div class="sgh-card md:flex-1">
                <div class="sgh-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('dashboard.disbursed_30d') }}</span>
                        <x-tabler-chart-line class="text-lg text-green-500" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold">{{ number_format((float) ($summary['disbursed_total_30d'] ?? 0), 2) }}</span>
                        <span class="text-sm text-muted-foreground">{{ __('dashboard.requests_created_30d') }}: {{ $summary['requests_created_30d'] ?? 0 }}</span>
                    </div>
                </div>
            </div>

            <div class="sgh-card md:flex-1">
                <div class="sgh-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('dashboard.overdue_advances') }}</span>
                        <x-tabler-clock-filled class="text-lg text-danger" />
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold">{{ $summary['overdue_advances'] ?? 0 }}</span>
                        <span class="text-sm text-muted-foreground">{{ __('dashboard.send_back_rate_30d') }}: {{ number_format((float) ($summary['send_back_rate_30d'] ?? 0), 1) }}%</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2 lg:gap-7.5">
            <div class="sgh-card">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title">{{ __('dashboard.personal_workload') }}</h3>
                </div>
                <div class="sgh-card-content p-5">
                    <div class="space-y-3 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">{{ __('dashboard.my_draft_requests') }}</span>
                            <span class="font-semibold">{{ $personal['my_draft_requests'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">{{ __('dashboard.my_in_workflow_requests') }}</span>
                            <span class="font-semibold">{{ $personal['my_in_workflow_requests'] ?? 0 }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-muted-foreground">{{ __('dashboard.my_draft_retirements') }}</span>
                            <span class="font-semibold">{{ $personal['my_draft_retirements'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="sgh-card">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title">{{ __('dashboard.approval_aging') }}</h3>
                </div>
                <div class="sgh-card-content p-5">
                    <div class="space-y-3 text-sm">
                        @foreach(($pipeline['approval_aging_buckets'] ?? []) as $bucket)
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">{{ $bucket['bucket'] }}</span>
                                <span class="font-semibold">{{ $bucket['count'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-2 lg:gap-7.5">
            <div class="sgh-card">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title">{{ __('dashboard.monthly_spend_trend') }}</h3>
                </div>
                <div class="sgh-card-content p-5">
                    <div class="space-y-2 text-sm">
                        @forelse(($trends['monthly_spend'] ?? []) as $row)
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">{{ $row['month_label'] }}</span>
                                <span class="font-semibold">{{ number_format((float) $row['total'], 2) }} <span class="text-xs text-muted-foreground">({{ $row['count'] }})</span></span>
                            </div>
                        @empty
                            <div class="text-muted-foreground">{{ __('dashboard.no_data') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="sgh-card">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title">{{ __('dashboard.top_spending_branches_30d') }}</h3>
                </div>
                <div class="sgh-card-content p-5">
                    <div class="space-y-2 text-sm">
                        @forelse(($insights['top_spending_branches_30d'] ?? []) as $branch)
                            <div class="flex items-center justify-between">
                                <span class="text-muted-foreground">{{ $branch['branch_name'] }}</span>
                                <span class="font-semibold">{{ number_format((float) $branch['total'], 2) }} <span class="text-xs text-muted-foreground">({{ $branch['count'] }})</span></span>
                            </div>
                        @empty
                            <div class="text-muted-foreground">{{ __('dashboard.no_data') }}</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
