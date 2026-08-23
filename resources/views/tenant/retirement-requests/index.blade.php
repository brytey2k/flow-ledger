@extends('tenant.layouts.base')

@php
    $statusColors = [
        'draft'       => 'sgh-badge-outline',
        'in_workflow' => 'sgh-badge-primary',
        'approved'    => 'sgh-badge-success',
        'settled'     => 'sgh-badge-info',
        'sent_back'   => 'sgh-badge-warning',
        'cancelled'   => 'sgh-badge-danger',
    ];
    $diffTypeLabels = [
        'pay_to_staff'       => ['label' => __('retirements.status.pay_to_staff'), 'class' => 'sgh-badge-warning'],
        'refund_to_company'  => ['label' => __('retirements.status.refund_company'), 'class' => 'sgh-badge-danger'],
        'nil'                => ['label' => __('retirements.status.nil'), 'class' => 'sgh-badge-outline'],
    ];
@endphp

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('retirements.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('retirements.subtitle') }}
            </div>
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('retirements.all') }}</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                    {{ $retirements->total() }} {{ Str::plural('Retirement', $retirements->total()) }}
                </span>
            </div>

            @if($retirements->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-file-arrow-left class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('retirements.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground">{{ __('retirements.empty.subtext') }}</p>
                    </div>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">#</span></span></th>
                                    <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('retirements.columns.advance') }}</span></span></th>
                                    <th class="min-w-[160px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.staff') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('retirements.columns.amount_expended') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('retirements.columns.difference') }}</span></span></th>
                                    <th class="min-w-[110px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.status') }}</span></span></th>
                                    <th class="min-w-[110px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.date') }}</span></span></th>
                                    <th class="min-w-[90px] text-center"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
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
                                            @php $dt = $diffTypeLabels[$ret->difference_type] ?? ['label' => '—', 'class' => 'sgh-badge-outline']; @endphp
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm font-medium text-mono">
                                                    {{ $pr->currency->symbol ?? '' }} {{ number_format((float) $ret->difference_amount, 2) }}
                                                </span>
                                                <span class="sgh-badge sgh-badge-sm {{ $dt['class'] }}">{{ $dt['label'] }}</span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm {{ $statusColors[$ret->status] ?? 'sgh-badge-outline' }}">
                                                {{ ucwords(str_replace('_', ' ', $ret->status)) }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $ret->created_at->format('M d, Y') }}</span></td>
                                        <td class="text-center">
                                            <a href="{{ route('retirement-requests.show', $ret) }}"
                                               class="sgh-btn sgh-btn-sm sgh-btn-outline">
                                                <x-tabler-eye-filled />
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
                    <div class="sgh-card-footer py-4 px-5 lg:px-7.5">
                        {{ $retirements->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</div>
@endsection
