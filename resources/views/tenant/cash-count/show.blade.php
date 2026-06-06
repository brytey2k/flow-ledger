@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cash_count.show_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $branch->name }} · {{ $cashCount->counted_at->format('M d, Y h:i A') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('cash-count.index', $branch) }}" class="kt-btn kt-btn-light">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('cash_count.buttons.back_to_history') }}
            </a>
            @can(\App\Enums\Tenant\PermissionKey::CreateCashCount->value)
                <a href="{{ route('cash-count.create', $branch) }}" class="kt-btn kt-btn-primary">
                    <i class="ki-filled ki-plus"></i>
                    {{ __('cash_count.buttons.new_count') }}
                </a>
            @endcan
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        <!-- Status Card -->
        @php $status = $cashCount->status(); @endphp
        <div class="kt-card">
            <div class="kt-card-content p-6 lg:p-8">
                <div class="flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-center gap-4">
                        @if($status === 'equal')
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-success/10">
                                <i class="ki-filled ki-check-circle text-3xl text-success"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-success">{{ __('cash_count.status.equal') }}</p>
                                <p class="text-sm text-secondary-foreground">{{ __('cash_count.labels.difference') }}: 0.00</p>
                            </div>
                        @elseif($status === 'surplus')
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-warning/10">
                                <i class="ki-filled ki-arrow-up-circle text-3xl text-warning"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-warning">{{ __('cash_count.status.surplus') }}</p>
                                <p class="text-sm text-secondary-foreground">
                                    +{{ $cashbook->currency->symbol }} {{ number_format(abs((float) $cashCount->difference), 2) }} {{ __('cash_count.labels.difference') }}
                                </p>
                            </div>
                        @else
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-danger/10">
                                <i class="ki-filled ki-arrow-down-circle text-3xl text-danger"></i>
                            </div>
                            <div>
                                <p class="text-2xl font-bold text-danger">{{ __('cash_count.status.deficit') }}</p>
                                <p class="text-sm text-secondary-foreground">
                                    −{{ $cashbook->currency->symbol }} {{ number_format(abs((float) $cashCount->difference), 2) }} {{ __('cash_count.labels.difference') }}
                                </p>
                            </div>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-6 text-right sm:grid-cols-3">
                        <div class="flex flex-col gap-0.5">
                            <span class="text-xs text-secondary-foreground">{{ __('cash_count.labels.counted_total') }}</span>
                            <span class="text-lg font-semibold text-mono">
                                {{ $cashbook->currency->symbol }} {{ number_format((float) $cashCount->counted_total, 2) }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-xs text-secondary-foreground">{{ __('cash_count.labels.balance_at_count') }}</span>
                            <span class="text-lg font-semibold text-mono">
                                {{ $cashbook->currency->symbol }} {{ number_format((float) $cashCount->cashbook_balance_at_count, 2) }}
                            </span>
                        </div>
                        <div class="flex flex-col gap-0.5">
                            <span class="text-xs text-secondary-foreground">{{ __('cash_count.labels.counted_by') }}</span>
                            <span class="text-sm font-medium text-foreground">{{ $cashCount->countedBy?->name ?? '—' }}</span>
                        </div>
                    </div>
                </div>

                @if($cashCount->notes)
                    <div class="mt-5 rounded-lg bg-muted p-4">
                        <p class="text-sm text-foreground">{{ $cashCount->notes }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Denomination Breakdown -->
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('cash_count.denominations.title') }}</h3>
                <span class="text-sm text-secondary-foreground">
                    {{ $cashbook->currency->name }} ({{ $cashbook->currency->symbol }})
                </span>
            </div>
            <div class="kt-card-table">
                <div class="kt-scrollable-x-auto border-b border-border">
                    <table class="kt-table kt-table-border">
                        <thead>
                            <tr>
                                <th class="min-w-[200px]">
                                    <span class="kt-table-col">
                                        <span class="kt-table-col-label">{{ __('cash_count.labels.denomination') }}</span>
                                    </span>
                                </th>
                                <th class="min-w-[140px] text-right">
                                    <span class="kt-table-col justify-end">
                                        <span class="kt-table-col-label">{{ __('cash_count.denominations.labels.value') }}</span>
                                    </span>
                                </th>
                                <th class="min-w-[100px] text-right">
                                    <span class="kt-table-col justify-end">
                                        <span class="kt-table-col-label">{{ __('cash_count.labels.quantity') }}</span>
                                    </span>
                                </th>
                                <th class="min-w-[140px] text-right">
                                    <span class="kt-table-col justify-end">
                                        <span class="kt-table-col-label">{{ __('cash_count.labels.subtotal') }}</span>
                                    </span>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($cashCount->items->sortBy('denomination_value') as $item)
                                <tr class="{{ $item->quantity === 0 ? 'opacity-40' : '' }}">
                                    <td>
                                        <span class="text-sm font-medium text-mono">{{ $item->denomination_label }}</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="text-sm text-foreground">
                                            {{ $cashbook->currency->symbol }} {{ number_format((float) $item->denomination_value, 2) }}
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <span class="text-sm font-medium text-foreground">{{ $item->quantity }}</span>
                                    </td>
                                    <td class="text-right">
                                        <span class="text-sm font-medium {{ $item->quantity > 0 ? 'text-mono' : 'text-secondary-foreground' }}">
                                            {{ $cashbook->currency->symbol }} {{ number_format((float) $item->subtotal, 2) }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="border-t-2 border-border">
                                <td colspan="3" class="text-right">
                                    <span class="text-sm font-semibold text-foreground">{{ __('cash_count.labels.counted_total') }}</span>
                                </td>
                                <td class="text-right">
                                    <span class="text-sm font-bold text-mono">
                                        {{ $cashbook->currency->symbol }} {{ number_format((float) $cashCount->counted_total, 2) }}
                                    </span>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Delete Action -->
        @can(\App\Enums\Tenant\PermissionKey::DeleteCashCount->value)
            <div class="flex justify-end">
                <form action="{{ route('cash-count.destroy', [$branch, $cashCount]) }}"
                      method="POST"
                      onsubmit="return confirm('{{ __('cash_count.confirm_delete') }}');">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="kt-btn kt-btn-light text-danger">
                        <i class="ki-filled ki-trash"></i>
                        {{ __('common.delete') }}
                    </button>
                </form>
            </div>
        @endcan

    </div>
</div>
@endsection
