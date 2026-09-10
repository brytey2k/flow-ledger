@extends('tenant.layouts.base')

@php
    $typeColors = [
        \App\Enums\Tenant\PaymentRequestType::Advance->value => 'sgh-badge-primary',
        \App\Enums\Tenant\PaymentRequestType::Expense->value => 'sgh-badge-warning',
    ];
@endphp

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('disbursements.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('disbursements.subtitle') }}
            </div>
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    <x-index-filters :action="route('disbursements.index')" :reset-url="route('disbursements.index')" :filters="$filters" search-placeholder="Search request ID or staff…">
        <div class="flex flex-col gap-1">
            <label for="branch_id" class="sgh-form-label">{{ __('common.columns.branch') }}</label>
            <select id="branch_id" name="branch_id" class="sgh-select w-full">
                <option value="">{{ __('common.all') }}</option>
                @foreach($branches as $branchId => $branchName)
                    <option value="{{ $branchId }}" @selected(($filters['branch_id'] ?? null) === $branchId)>{{ $branchName }}</option>
                @endforeach
            </select>
        </div>
    </x-index-filters>

    <div class="grid gap-5 lg:gap-7.5">

        @if($cashPositions->isNotEmpty())
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach($cashPositions as $position)
                    <div class="sgh-card p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <div class="text-sm font-semibold text-mono">{{ $position['cashbook']->branch?->name ?? 'Unknown Branch' }}</div>
                                <div class="text-xs text-secondary-foreground">Cash available for release</div>
                            </div>
                            <x-tabler-calculator-filled class="text-2xl text-muted-foreground" />
                        </div>
                        <div class="mt-4 text-2xl font-bold {{ $position['available_uncommitted_cash'] < 0 ? 'text-danger' : 'text-mono' }}">
                            {{ $position['cashbook']->currency?->symbol ?? '' }} {{ number_format((float) $position['available_uncommitted_cash'], 2) }}
                        </div>
                        <div class="mt-3 grid grid-cols-2 gap-3 border-t border-border pt-3 text-xs">
                            <div>
                                <div class="text-secondary-foreground">Cashbook balance</div>
                                <div class="mt-0.5 font-semibold text-mono">{{ number_format((float) $position['current_balance'], 2) }}</div>
                            </div>
                            <div>
                                <div class="text-secondary-foreground">Approved commitments</div>
                                <div class="mt-0.5 font-semibold text-warning">{{ number_format((float) $position['approved_awaiting_release'], 2) }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('disbursements.pending_card') }}</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                    {{ $requests->total() }} {{ Str::plural('Request', $requests->total()) }}
                </span>
            </div>

            @if($requests->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-currency-dollar class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('disbursements.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground">{{ __('disbursements.empty.subtext') }}</p>
                    </div>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">#</span></span></th>
                                    <th class="min-w-[160px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.staff') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.branch') }}</span></span></th>
                                    <th class="min-w-[80px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.type') }}</span></span></th>
                                    <th class="min-w-[140px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.amount') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('disbursements.columns.approved') }}</span></span></th>
                                    <th class="min-w-[90px] text-center"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $req)
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $req->id }}</span></td>
                                        <td>
                                            <span class="text-sm font-medium text-mono">{{ $req->staff->full_name ?? '—' }}</span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $req->branch->name ?? '—' }}</span></td>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm {{ $typeColors[$req->type] ?? 'sgh-badge-outline' }}">
                                                {{ ucfirst($req->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium text-mono">
                                                {{ $req->currency->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">
                                                {{ $req->approved_at?->format('M d, Y') ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('payment-requests.show', $req) }}"
                                               class="sgh-btn sgh-btn-sm sgh-btn-primary">
                                                <x-tabler-currency-dollar />
                                                {{ __('disbursements.buttons.disburse') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($requests->hasPages())
                    <div class="sgh-card-footer py-4 px-5 lg:px-7.5">
                        {{ $requests->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</div>
@endsection
