@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cash_count.history_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $branch->name }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a href="{{ route('cashbook.index', $branch) }}" class="kt-btn kt-btn-light">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('common.back') }}
            </a>
            @can(\App\Enums\Tenant\PermissionKey::CreateCashCount->value)
                <a class="kt-btn kt-btn-primary" href="{{ route('cash-count.create', $branch) }}">
                    <i class="ki-filled ki-plus"></i>
                    {{ __('cash_count.buttons.count_cash') }}
                </a>
            @endcan
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Cash Count History Table -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('cash_count.history_title') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $cashCounts->total() }} {{ Str::plural('Count', $cashCounts->total()) }}
                    </span>
                </div>
            </div>

            @if($cashCounts->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-finance-calculator text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('cash_count.empty.title') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('cash_count.empty.description') }}</p>
                        @can(\App\Enums\Tenant\PermissionKey::CreateCashCount->value)
                            <a href="{{ route('cash-count.create', $branch) }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                {{ __('cash_count.buttons.count_cash') }}
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
                                    <th class="min-w-[160px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('cash_count.labels.counted_at') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[160px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('cash_count.labels.counted_by') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[140px] text-right">
                                        <span class="kt-table-col justify-end">
                                            <span class="kt-table-col-label">{{ __('cash_count.labels.counted_total') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[140px] text-right">
                                        <span class="kt-table-col justify-end">
                                            <span class="kt-table-col-label">{{ __('cash_count.labels.balance_at_count') }}</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('cash_count.labels.difference') }}</span>
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
                                @foreach($cashCounts as $cashCount)
                                    @php $status = $cashCount->status(); @endphp
                                    <tr>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm text-foreground">{{ $cashCount->counted_at->format('M d, Y') }}</span>
                                                <span class="text-2sm text-secondary-foreground">{{ $cashCount->counted_at->format('h:i A') }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $cashCount->countedBy?->name ?? '—' }}</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-sm font-medium text-foreground">
                                                {{ $cashbook->currency->symbol }} {{ number_format((float) $cashCount->counted_total, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-sm text-secondary-foreground">
                                                {{ $cashbook->currency->symbol }} {{ number_format((float) $cashCount->cashbook_balance_at_count, 2) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if($status === 'equal')
                                                <span class="badge badge-sm kt-badge kt-badge-success">{{ __('cash_count.status.equal') }}</span>
                                            @elseif($status === 'surplus')
                                                <span class="badge badge-sm kt-badge kt-badge-warning">
                                                    +{{ $cashbook->currency->symbol }} {{ number_format(abs((float) $cashCount->difference), 2) }}
                                                </span>
                                            @else
                                                <span class="badge badge-sm kt-badge kt-badge-danger">
                                                    −{{ $cashbook->currency->symbol }} {{ number_format(abs((float) $cashCount->difference), 2) }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                @can(\App\Enums\Tenant\PermissionKey::AccessCashCount->value)
                                                    <a href="{{ route('cash-count.show', [$branch, $cashCount]) }}"
                                                       class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-primary"
                                                       title="{{ __('common.view') }}">
                                                        <i class="ki-filled ki-eye text-lg"></i>
                                                    </a>
                                                @endcan
                                                @can(\App\Enums\Tenant\PermissionKey::DeleteCashCount->value)
                                                    <form action="{{ route('cash-count.destroy', [$branch, $cashCount]) }}"
                                                          method="POST"
                                                          onsubmit="return confirm('{{ __('cash_count.confirm_delete') }}');"
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
                @if($cashCounts->hasPages())
                    <div class="kt-card-footer p-5">
                        {{ $cashCounts->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
<!-- End of Cash Count History Table -->
@endsection
