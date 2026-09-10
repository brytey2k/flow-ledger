@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cash_count.denominations.title') }} — {{ $currency->name }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $currency->symbol }} · {{ $currency->short_name }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('currencies.index') }}" class="sgh-btn sgh-btn-light">
                <x-tabler-arrow-left />
                {{ __('common.back') }}
            </a>
            @can(\App\Enums\Tenant\PermissionKey::ManageCurrencyDenominations->value)
                <a class="sgh-btn sgh-btn-primary" href="{{ route('currency.denominations.create', $currency) }}">
                    <x-tabler-plus-filled />
                    {{ __('cash_count.denominations.add') }}
                </a>
            @endcan
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Denominations Table -->
<div class="sgh-container-fixed">
    <x-index-filters :action="route('currency.denominations.index', $currency)" :reset-url="route('currency.denominations.index', $currency)" :filters="$filters" search-placeholder="Search denomination labels…">
        <div class="flex flex-col gap-1">
            <label for="type" class="sgh-form-label">{{ __('common.columns.type') }}</label>
            <select id="type" name="type" class="sgh-select w-full">
                <option value="">{{ __('common.all_types') }}</option>
                @foreach(\App\Enums\Tenant\CurrencyDenominationType::cases() as $denominationType)
                    <option value="{{ $denominationType->value }}" @selected(($filters['type'] ?? null) === $denominationType->value)>{{ $denominationType->label() }}</option>
                @endforeach
            </select>
        </div>
    </x-index-filters>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('cash_count.denominations.title') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $denominations->count() }} {{ Str::plural('Denomination', $denominations->count()) }}
                    </span>
                </div>
            </div>

            @if($denominations->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-calculator-filled class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('cash_count.denominations.empty_title') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('cash_count.denominations.empty_description') }}</p>
                        @can(\App\Enums\Tenant\PermissionKey::ManageCurrencyDenominations->value)
                            <a href="{{ route('currency.denominations.create', $currency) }}" class="sgh-btn sgh-btn-primary">
                                <x-tabler-plus-filled />
                                {{ __('cash_count.denominations.add') }}
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
                                    <th class="min-w-[200px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('cash_count.denominations.labels.label') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('cash_count.denominations.labels.type') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[160px] text-right">
                                        <span class="sgh-table-col justify-end">
                                            <span class="sgh-table-col-label">{{ __('cash_count.denominations.labels.value') }} ({{ $currency->symbol }})</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.actions') }}</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($denominations as $denomination)
                                    <tr>
                                        <td>
                                            <span class="text-sm font-medium text-mono">{{ $denomination->label }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-sm badge-outline">
                                                {{ __('cash_count.denominations.types.' . $denomination->type->value) }}
                                            </span>
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
                                                       class="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-primary"
                                                       title="{{ __('common.edit') }}">
                                                        <x-tabler-edit-filled class="text-lg" />
                                                    </a>
                                                    <form action="{{ route('currency.denominations.destroy', [$currency, $denomination]) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('{{ __('cash_count.denominations.confirm_delete') }}');"
                                                          class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-danger" title="{{ __('common.delete') }}">
                                                            <x-tabler-trash-filled class="text-lg" />
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
