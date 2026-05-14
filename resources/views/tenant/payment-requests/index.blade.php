@extends('tenant.layouts.base')

@php
    use App\Enums\Tenant\PermissionKey;

    $statusColors = [
        'draft'       => 'kt-badge-outline',
        'in_workflow' => 'kt-badge-primary',
        'approved'    => 'kt-badge-success',
        'disbursed'   => 'kt-badge-info',
        'retired'     => 'kt-badge-neutral',
        'sent_back'   => 'kt-badge-warning',
        'cancelled'   => 'kt-badge-danger',
    ];
    $typeColors = [
        'advance' => 'kt-badge-primary',
        'expense' => 'kt-badge-warning',
    ];
@endphp

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('payment_requests.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('payment_requests.subtitle') }}
            </div>
        </div>
        @can(PermissionKey::CreatePaymentRequest->value)
            <a class="kt-btn kt-btn-primary" href="{{ route('payment-requests.create') }}">
                <i class="ki-filled ki-plus"></i>
                {{ __('payment_requests.add_new') }}
            </a>
        @endcan
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('payment_requests.all') }}</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">
                    {{ $requests->total() }} {{ Str::plural('Request', $requests->total()) }}
                </span>
            </div>

            @if($requests->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-wallet text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('payment_requests.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('payment_requests.empty.subtext') }}</p>
                        @can(PermissionKey::CreatePaymentRequest->value)
                            <a href="{{ route('payment-requests.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                {{ __('payment_requests.add_new') }}
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
                                    <th class="min-w-[60px]"><span class="kt-table-col"><span class="kt-table-col-label">#</span></span></th>
                                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.staff') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.branch') }}</span></span></th>
                                    <th class="min-w-[80px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.type') }}</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.amount') }}</span></span></th>
                                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.status') }}</span></span></th>
                                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.date') }}</span></span></th>
                                    <th class="min-w-[90px] text-center"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
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
                                            <span class="kt-badge kt-badge-sm {{ $typeColors[$req->type] ?? 'kt-badge-outline' }}">
                                                {{ ucfirst($req->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium text-mono">
                                                {{ $req->currency->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="kt-badge kt-badge-sm {{ $statusColors[$req->status] ?? 'kt-badge-outline' }}">
                                                {{ ucwords(str_replace('_', ' ', $req->status)) }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $req->created_at->format('M d, Y') }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('payment-requests.show', $req) }}"
                                               class="kt-btn kt-btn-sm kt-btn-outline">
                                                <i class="ki-filled ki-eye"></i>
                                                {{ __('common.view') }}
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($requests->hasPages())
                    <div class="kt-card-footer py-4 px-5 lg:px-7.5">
                        {{ $requests->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
