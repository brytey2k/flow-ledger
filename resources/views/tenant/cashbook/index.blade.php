@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cashbook.index.title', ['branch' => $branch->name]) }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $cashbook->currency->name }} ({{ $cashbook->currency->symbol }}) cash ledger
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            @can(\App\Enums\Tenant\PermissionKey::AccessCashCount->value)
                <a class="sgh-btn sgh-btn-light" href="{{ route('cash-count.index', $branch) }}">
                    <x-tabler-clock-filled class="text-sm" />
                    {{ __('cash_count.history_title') }}
                </a>
            @endcan
            @can(\App\Enums\Tenant\PermissionKey::AccessCashbook->value)
                <a class="sgh-btn sgh-btn-light"
                   href="{{ route('cashbook.export', array_merge(['branch' => $branch->id], array_filter($filters))) }}">
                    <x-tabler-arrow-down-to-arc />
                    {{ __('cashbook.index.export') }}
                </a>
            @endcan
            @can(\App\Enums\Tenant\PermissionKey::CreateCashCount->value)
                <a class="sgh-btn sgh-btn-light" href="{{ route('cash-count.create', $branch) }}">
                    <x-tabler-calculator-filled />
                    {{ __('cash_count.buttons.count_cash') }}
                </a>
            @endcan
            @can(\App\Enums\Tenant\PermissionKey::CreateCashbookEntry->value)
                <a class="sgh-btn sgh-btn-primary" href="{{ route('cashbook.create', $branch) }}">
                    <x-tabler-plus-filled />
                    {{ __('cashbook.index.add_receipt') }}
                </a>
            @endcan
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Balance Card -->
<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('cashbook.index.balance_card') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline text-lg font-semibold">
                        {{ $cashbook->currency->symbol }} {{ number_format((float) $cashbook->balance, 2) }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End of Balance Card -->

<!-- Filters -->
<div class="sgh-container-fixed mt-5">
    <form method="GET" action="{{ route('cashbook.index', $branch) }}" class="sgh-card p-5">
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-6">
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-secondary-foreground">{{ __('cashbook.filter.date_from') }}</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                       class="sgh-input sgh-input-sm" max="{{ today()->toDateString() }}">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-secondary-foreground">{{ __('cashbook.filter.date_to') }}</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                       class="sgh-input sgh-input-sm" max="{{ today()->toDateString() }}">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-secondary-foreground">{{ __('cashbook.filter.type') }}</label>
                <select name="type" class="sgh-select sgh-select-sm">
                    <option value="">{{ __('common.all') }}</option>
                    <option value="debit"  {{ ($filters['type'] ?? '') === 'debit'  ? 'selected' : '' }}>{{ __('cashbook.entry_types.debit') }}</option>
                    <option value="credit" {{ ($filters['type'] ?? '') === 'credit' ? 'selected' : '' }}>{{ __('cashbook.entry_types.credit') }}</option>
                </select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-secondary-foreground">{{ __('cashbook.filter.description') }}</label>
                <input type="text" name="description" value="{{ $filters['description'] ?? '' }}"
                       placeholder="{{ __('cashbook.filter.description_placeholder') }}"
                       class="sgh-input sgh-input-sm">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-secondary-foreground">{{ __('cashbook.filter.amount_min') }}</label>
                <input type="number" name="amount_min" value="{{ $filters['amount_min'] ?? '' }}"
                       min="0" step="0.01" class="sgh-input sgh-input-sm">
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-xs font-medium text-secondary-foreground">{{ __('cashbook.filter.amount_max') }}</label>
                <input type="number" name="amount_max" value="{{ $filters['amount_max'] ?? '' }}"
                       min="0" step="0.01" class="sgh-input sgh-input-sm">
            </div>
        </div>
        <div class="flex items-center gap-2 mt-4">
            <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-primary">{{ __('common.apply_filters') }}</button>
            <a href="{{ route('cashbook.index', $branch) }}" class="sgh-btn sgh-btn-sm sgh-btn-light">{{ __('common.clear_filters') }}</a>
        </div>
    </form>
</div>
<!-- End Filters -->

<!-- Entries Table -->
<div class="sgh-container-fixed mt-5">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('cashbook.index.entries_card') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $entries->total() }} {{ Str::plural('Entry', $entries->total()) }}
                    </span>
                </div>
            </div>

            @if($entries->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-currency-dollar class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('cashbook.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('cashbook.empty.subtext') }}</p>
                        @can(\App\Enums\Tenant\PermissionKey::CreateCashbookEntry->value)
                            <a href="{{ route('cashbook.create', $branch) }}" class="sgh-btn sgh-btn-primary">
                                <x-tabler-plus-filled />
                                {{ __('cashbook.empty.add_manual') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[130px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.date') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[250px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.description') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[140px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.reference') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.type') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[140px] text-right">
                                        <span class="sgh-table-col justify-end">
                                            <span class="sgh-table-col-label">{{ __('common.columns.amount') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[80px] text-center">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.actions') }}</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entries as $entry)
                                    <tr>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $entry->entry_date->format('M d, Y') }}</span>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm font-medium text-mono">{{ $entry->description }}</span>
                                                @if($entry->notes)
                                                    <span class="text-2sm text-secondary-foreground">{{ Str::limit($entry->notes, 60) }}</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $entry->reference ?? '—' }}</span>
                                        </td>
                                        <td class="text-center">
                                            @if($entry->type === 'debit')
                                                <span class="badge badge-sm sgh-badge sgh-badge-success">{{ __('cashbook.entry_types.debit') }}</span>
                                            @else
                                                <span class="badge badge-sm sgh-badge sgh-badge-danger">{{ __('cashbook.entry_types.credit') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <span class="text-sm font-medium {{ $entry->type === 'debit' ? 'text-success' : 'text-danger' }}">
                                                {{ $cashbook->currency->symbol }} {{ number_format((float) $entry->amount, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($entry->isManual())
                                                @can(\App\Enums\Tenant\PermissionKey::DeleteCashbookEntry->value)
                                                    <form action="{{ route('cashbook.destroy', [$branch, $entry]) }}" method="POST"
                                                          onsubmit="return confirm('{{ __('cashbook.confirm_delete') }}');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-danger" title="{{ __('common.delete') }}">
                                                            <x-tabler-trash-filled class="text-lg" />
                                                        </button>
                                                    </form>
                                                @endcan
                                            @else
                                                <span class="text-xs text-secondary-foreground">{{ __('common.auto') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($entries->hasPages())
                    <div class="sgh-card-footer p-5">
                        {{ $entries->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
<!-- End of Entries Table -->
@endsection
