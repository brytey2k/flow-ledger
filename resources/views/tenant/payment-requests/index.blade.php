@extends('tenant.layouts.base')

@php
    use App\Enums\Tenant\PermissionKey;

    $statusColors = [
        'draft'       => 'sgh-badge-outline',
        'in_workflow' => 'sgh-badge-primary',
        'approved'    => 'sgh-badge-success',
        'disbursed'   => 'sgh-badge-info',
        'retired'     => 'sgh-badge-neutral',
        'sent_back'   => 'sgh-badge-warning',
        'cancelled'   => 'sgh-badge-danger',
    ];
    $typeColors = [
        'advance' => 'sgh-badge-primary',
        'expense' => 'sgh-badge-warning',
    ];
@endphp

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('payment_requests.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('payment_requests.subtitle') }}
            </div>
        </div>
        @can(PermissionKey::CreatePaymentRequest->value)
            <a class="sgh-btn sgh-btn-primary" href="{{ route('payment-requests.create') }}">
                <x-tabler-plus-filled />
                {{ __('payment_requests.add_new') }}
            </a>
        @endcan
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card p-5">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">{{ __('payment_requests.filters.status_label') }}</label>
                    <select name="status" class="sgh-select sgh-select-sm">
                        @foreach(__('payment_requests.filters.status_options') as $value => $label)
                            <option value="{{ $value === 'all' ? '' : $value }}" @selected(($status ?? '') === ($value === 'all' ? '' : $value))>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">{{ __('payment_requests.filters.scope_label') }}</label>
                    <select name="scope" class="sgh-select sgh-select-sm">
                        @foreach(__('payment_requests.filters.scope_options') as $value => $label)
                            <option value="{{ $value }}" @selected(($scope ?? 'branch') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-primary">{{ __('payment_requests.filters.apply') }}</button>
                <a href="{{ route('payment-requests.index') }}" class="sgh-btn sgh-btn-sm sgh-btn-light">Reset</a>
            </form>
        </div>

        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('payment_requests.all') }}</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                    {{ $requests->total() }} {{ Str::plural('Request', $requests->total()) }}
                </span>
            </div>

            @if($requests->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-wallet class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('payment_requests.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('payment_requests.empty.subtext') }}</p>
                        @can(PermissionKey::CreatePaymentRequest->value)
                            <a href="{{ route('payment-requests.create') }}" class="sgh-btn sgh-btn-primary">
                                <x-tabler-plus-filled />
                                {{ __('payment_requests.add_new') }}
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
                                    <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">#</span></span></th>
                                    <th class="min-w-[160px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.staff') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.branch') }}</span></span></th>
                                    <th class="min-w-[80px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.type') }}</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.amount') }}</span></span></th>
                                    <th class="min-w-[110px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.status') }}</span></span></th>
                                    <th class="min-w-[110px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.date') }}</span></span></th>
                                    <th class="min-w-[180px] text-center"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($requests as $req)
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $req->id }}</span></td>
                                        <td>
                                            <span class="text-sm font-medium text-mono">
                                                {{ $req->staff->full_name ?? '—' }}
                                            </span>
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
                                            <span class="sgh-badge sgh-badge-sm {{ $statusColors[$req->status] ?? 'sgh-badge-outline' }}">
                                                {{ ucwords(str_replace('_', ' ', $req->status)) }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $req->created_at->format('M d, Y') }}</span></td>
                                        <td class="text-center">
                                            @php
                                                $canRetire = $req->isAdvance() && $req->isDisbursed() && $req->retirementRequests->isEmpty();
                                                $isOwner = isset($currentStaffId) && $currentStaffId === $req->staff_id;
                                            @endphp
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('payment-requests.show', $req) }}"
                                                   class="sgh-btn sgh-btn-sm sgh-btn-outline">
                                                    <x-tabler-eye-filled />
                                                    {{ __('common.view') }}
                                                </a>
                                                @can(PermissionKey::CreateRetirementRequest->value)
                                                    @if($canRetire && $isOwner)
                                                        <a href="{{ route('retirement-requests.create', $req) }}"
                                                           class="sgh-btn sgh-btn-sm sgh-btn-primary">
                                                            <x-tabler-file-arrow-left />
                                                            {{ __('payment_requests.buttons.retire') }}
                                                        </a>
                                                    @endif
                                                @endcan
                                            </div>
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
