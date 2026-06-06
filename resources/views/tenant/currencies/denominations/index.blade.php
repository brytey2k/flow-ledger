@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cash_count.denominations.title') }} — {{ $currency->name }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $currency->symbol }} · {{ $currency->short_name }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('currencies.index') }}" class="kt-btn kt-btn-light">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('common.back') }}
            </a>
            @can(\App\Enums\Tenant\PermissionKey::ManageCurrencyDenominations->value)
                <a class="kt-btn kt-btn-primary" href="{{ route('currency.denominations.create', $currency) }}">
                    <i class="ki-filled ki-plus"></i>
                    {{ __('cash_count.denominations.add') }}
                </a>
            @endcan
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Denominations Table -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('cash_count.denominations.title') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $denominations->count() }} {{ Str::plural('Denomination', $denominations->count()) }}
                    </span>
                </div>
            </div>

            @if($denominations->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-finance-calculator text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('cash_count.denominations.empty_title') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('cash_count.denominations.empty_description') }}</p>
                        @can(\App\Enums\Tenant\PermissionKey::ManageCurrencyDenominations->value)
                            <a href="{{ route('currency.denominations.create', $currency) }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                {{ __('cash_count.denominations.add') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[80px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('cash_count.denominations.labels.sort_order') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('cash_count.denominations.labels.label') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[160px] text-right">
                                        <span class="kt-table-col justify-end">
                                            <span class="kt-table-col-label">{{ __('cash_count.denominations.labels.value') }} ({{ $currency->symbol }})</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.actions') }}</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($denominations as $denomination)
                                    <tr>
                                        <td>
                                            <span class="text-sm text-secondary-foreground">{{ $denomination->sort_order }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium text-mono">{{ $denomination->label }}</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-sm font-medium text-foreground">
                                                {{ $currency->symbol }} {{ number_format((float) $denomination->value, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                @can(\App\Enums\Tenant\PermissionKey::ManageCurrencyDenominations->value)
                                                    <a href="{{ route('currency.denominations.edit', [$currency, $denomination]) }}"
                                                       class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-primary"
                                                       title="{{ __('common.edit') }}">
                                                        <i class="ki-filled ki-notepad-edit text-lg"></i>
                                                    </a>
                                                    <form action="{{ route('currency.denominations.destroy', [$currency, $denomination]) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('{{ __('cash_count.denominations.confirm_delete') }}');"
                                                          class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-danger" title="{{ __('common.delete') }}">
                                                            <i class="ki-filled ki-trash text-lg"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
<!-- End of Denominations Table -->
@endsection
