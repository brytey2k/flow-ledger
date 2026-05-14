@extends('tenant.layouts.base')

@php
    $statusColors = [
        'draft'       => 'kt-badge-outline',
        'in_workflow' => 'kt-badge-primary',
        'approved'    => 'kt-badge-success',
        'settled'     => 'kt-badge-info',
        'sent_back'   => 'kt-badge-warning',
        'cancelled'   => 'kt-badge-danger',
    ];
    $diffTypeLabels = [
        'pay_to_staff'       => ['label' => __('retirements.status.pay_to_staff'), 'class' => 'kt-badge-warning'],
        'refund_to_company'  => ['label' => __('retirements.status.refund_company'), 'class' => 'kt-badge-danger'],
        'nil'                => ['label' => __('retirements.status.nil'), 'class' => 'kt-badge-outline'],
    ];
@endphp

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('retirements.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('retirements.subtitle') }}
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('retirements.all') }}</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">
                    {{ $retirements->total() }} {{ Str::plural('Retirement', $retirements->total()) }}
                </span>
            </div>

            @if($retirements->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-file-up text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('retirements.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground">{{ __('retirements.empty.subtext') }}</p>
                    </div>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="kt-table-col"><span class="kt-table-col-label">#</span></span></th>
                                    <th class="min-w-[60px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('retirements.columns.advance') }}</span></span></th>
                                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.staff') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('retirements.columns.amount_expended') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('retirements.columns.difference') }}</span></span></th>
                                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.status') }}</span></span></th>
                                    <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.date') }}</span></span></th>
                                    <th class="min-w-[90px] text-center"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($retirements as $ret)
                                    @php $pr = $ret->paymentRequest; @endphp
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $ret->id }}</span></td>
                                        <td>
                                            <a href="{{ route('payment-requests.show', $pr) }}"
                                               class="text-sm text-primary hover:underline">#{{ $pr->id }}</a>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium text-mono">{{ $pr->staff->full_name ?? '—' }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm font-medium text-mono">
                                                {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $ret->total_amount_expended, 2) }}
                                            </span>
                                        </td>
                                        <td>
                                            @php $dt = $diffTypeLabels[$ret->difference_type] ?? ['label' => '—', 'class' => 'kt-badge-outline']; @endphp
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm font-medium text-mono">
                                                    {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $ret->difference_amount, 2) }}
                                                </span>
                                                <span class="kt-badge kt-badge-sm {{ $dt['class'] }}">{{ $dt['label'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="kt-badge kt-badge-sm {{ $statusColors[$ret->status] ?? 'kt-badge-outline' }}">
                                                {{ ucwords(str_replace('_', ' ', $ret->status)) }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $ret->created_at->format('M d, Y') }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('retirement-requests.show', $ret) }}"
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

                @if($retirements->hasPages())
                    <div class="kt-card-footer py-4 px-5 lg:px-7.5">
                        {{ $retirements->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</div>
@endsection
