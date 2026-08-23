@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>{{ $title }}</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ $title }}</h1>
            <p class="text-sm text-secondary-foreground">
                {{ $dateFrom }} — {{ $dateTo }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.breakdown'])
            <a href="javascript:history.back()" class="fl-btn fl-btn-light fl-btn-sm">
                <x-tabler-arrow-left class="text-xs" /> Back
            </a>
        </div>
    </div>
</div>

<div class="fl-container-fixed">
    <div class="fl-card fl-card-grid">
        <div class="fl-card-header">
            <h3 class="fl-card-title">Payment Requests</h3>
            <span class="fl-badge fl-badge-sm fl-badge-outline">{{ $rows->total() }} records</span>
        </div>

        @if($rows->isEmpty())
            <div class="fl-card-content flex flex-col items-center justify-center py-12">
                <x-tabler-file-text-filled class="text-5xl text-muted-foreground mb-3" />
                <p class="text-sm text-secondary-foreground">No payment requests found.</p>
            </div>
        @else
            <div class="fl-card-table">
                <div class="fl-scrollable-x-auto border-b border-border">
                    <table class="fl-table fl-table-border">
                        <thead>
                            <tr>
                                <th class="min-w-[60px]"><span class="fl-table-col"><span class="fl-table-col-label">#</span></span></th>
                                <th class="min-w-[160px]"><span class="fl-table-col"><span class="fl-table-col-label">Staff</span></span></th>
                                <th class="min-w-[120px]"><span class="fl-table-col"><span class="fl-table-col-label">Department</span></span></th>
                                <th class="min-w-[110px]"><span class="fl-table-col"><span class="fl-table-col-label">Branch</span></span></th>
                                <th class="min-w-[80px]"><span class="fl-table-col"><span class="fl-table-col-label">Type</span></span></th>
                                <th class="min-w-[130px]"><span class="fl-table-col"><span class="fl-table-col-label">Amount</span></span></th>
                                <th class="min-w-[110px]"><span class="fl-table-col"><span class="fl-table-col-label">Status</span></span></th>
                                <th class="min-w-[120px]"><span class="fl-table-col"><span class="fl-table-col-label">Date</span></span></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $req)
                                @php
                                    $statusColors = [
                                        'draft'       => 'fl-badge-outline',
                                        'in_workflow' => 'fl-badge-primary',
                                        'approved'    => 'fl-badge-success',
                                        'disbursed'   => 'fl-badge-info',
                                        'retired'     => 'fl-badge-neutral',
                                        'sent_back'   => 'fl-badge-warning',
                                        'cancelled'   => 'fl-badge-danger',
                                        'denied'      => 'fl-badge-danger',
                                        'settled'     => 'fl-badge-success',
                                    ];
                                    $statusClass = $statusColors[$req->status] ?? 'fl-badge-outline';
                                    $dateValue = $req->disbursed_at ?? $req->updated_at ?? $req->created_at;
                                @endphp
                                <tr>
                                    <td><span class="text-sm text-secondary-foreground">#{{ $req->id }}</span></td>
                                    <td><span class="text-sm font-medium text-mono">{{ $req->staff?->full_name ?? '—' }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $req->staff?->department?->name ?? '—' }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $req->branch?->name ?? '—' }}</span></td>
                                    <td><span class="fl-badge fl-badge-sm fl-badge-outline">{{ ucfirst($req->type) }}</span></td>
                                    <td><span class="text-sm font-semibold text-mono">{{ $req->currency?->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}</span></td>
                                    <td><span class="fl-badge fl-badge-sm {{ $statusClass }}">{{ ucwords(str_replace('_', ' ', $req->status)) }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $dateValue?->format('d M Y') ?? '—' }}</span></td>
                                    <td>
                                        <a href="{{ route('payment-requests.show', $req) }}" class="fl-btn fl-btn-xs fl-btn-light">
                                            View <x-tabler-arrow-right class="text-xs" />
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($rows->hasPages())
                <div class="fl-card-footer py-4 px-5 lg:px-7.5">
                    {{ $rows->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
