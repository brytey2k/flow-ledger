@extends('tenant.layouts.base')

@php
    use App\Enums\Tenant\PermissionKey;

    $typeColors = [
        \App\Enums\Tenant\PaymentRequestType::Advance->value => 'fl-badge-primary',
        \App\Enums\Tenant\PaymentRequestType::Expense->value => 'fl-badge-warning',
        \App\Enums\Tenant\PaymentRequestType::Retirement->value => 'fl-badge-success',
    ];
@endphp

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('approvals.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('approvals.subtitle') }}
            </div>
        </div>
    </div>
</div>

<div class="fl-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="fl-card fl-card-grid">
            <div class="fl-card-header">
                <h3 class="fl-card-title">{{ __('approvals.pending_reviews') }}</h3>
                <span class="fl-badge fl-badge-sm fl-badge-outline">
                    {{ $instanceStages->total() }} {{ Str::plural('item', $instanceStages->total()) }}
                </span>
            </div>

            @if($instanceStages->isEmpty())
                <div class="fl-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-shield-check-filled class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('approvals.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground">{{ __('approvals.empty.subtext') }}</p>
                    </div>
                </div>
            @else
                <div class="fl-card-table">
                    <div class="fl-scrollable-x-auto border-b border-border">
                        <table class="fl-table fl-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="fl-table-col"><span class="fl-table-col-label">#</span></span></th>
                                    <th class="min-w-[160px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.staff') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.branch') }}</span></span></th>
                                    <th class="min-w-[80px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.type') }}</span></span></th>
                                    <th class="min-w-[120px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.amount') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.stage') }}</span></span></th>
                                    <th class="min-w-[110px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.submitted') }}</span></span></th>
                                    <th class="min-w-[90px] text-center"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($instanceStages as $instanceStage)
                                    @php $req = $instanceStage->instance->workflowable; @endphp
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $req->id }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $req->staff->full_name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $req->branch->name ?? '—' }}</span></td>
                                        <td>
                                            <span class="fl-badge fl-badge-sm {{ $typeColors[$req->type] ?? 'fl-badge-outline' }}">
                                                {{ ucfirst($req->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium text-mono">
                                                {{ $req->currency->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $instanceStage->stage->name }}</span></td>
                                        <td>
                                            <span class="text-sm text-foreground">
                                                {{ $req->submitted_at?->format('M d, Y') ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('approvals.show', $instanceStage) }}"
                                               class="fl-btn fl-btn-sm fl-btn-primary">
                                                <x-tabler-eye-filled />
                                                {{ __('common.review') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($instanceStages->hasPages())
                    <div class="fl-card-footer py-4 px-5 lg:px-7.5">
                        {{ $instanceStages->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
