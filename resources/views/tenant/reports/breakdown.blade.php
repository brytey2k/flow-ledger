@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
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
            <a href="javascript:history.back()" class="sgh-btn sgh-btn-light sgh-btn-sm">
                <x-tabler-arrow-left class="text-xs" /> Back
            </a>
        </div>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="sgh-card sgh-card-grid">
        <div class="sgh-card-header">
            <h3 class="sgh-card-title">Payment Requests</h3>
            <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $rows->total() }} records</span>
        </div>

        @if($rows->isEmpty())
            <div class="sgh-card-content flex flex-col items-center justify-center py-12">
                <x-tabler-file-text-filled class="text-5xl text-muted-foreground mb-3" />
                <p class="text-sm text-secondary-foreground">No payment requests found.</p>
            </div>
        @else
            <div class="sgh-card-table">
                <div class="sgh-scrollable-x-auto border-b border-border">
                    <table class="sgh-table sgh-table-border">
                        <thead>
                            <tr>
                                <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">#</span></span></th>
                                <th class="min-w-[160px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Staff</span></span></th>
                                <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Department</span></span></th>
                                <th class="min-w-[110px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Branch</span></span></th>
                                <th class="min-w-[80px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Type</span></span></th>
                                <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Amount</span></span></th>
                                <th class="min-w-[110px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Status</span></span></th>
                                <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Date</span></span></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $req)
                                @php
                                    $statusColors = [
                                        'draft'       => 'sgh-badge-outline',
                                        'in_workflow' => 'sgh-badge-primary',
                                        'approved'    => 'sgh-badge-success',
                                        'disbursed'   => 'sgh-badge-info',
                                        'retired'     => 'sgh-badge-neutral',
                                        'sent_back'   => 'sgh-badge-warning',
                                        'cancelled'   => 'sgh-badge-danger',
                                        'denied'      => 'sgh-badge-danger',
                                        'settled'     => 'sgh-badge-success',
                                    ];
                                    $statusClass = $statusColors[$req->status] ?? 'sgh-badge-outline';
                                    $dateValue = $req->disbursed_at ?? $req->updated_at ?? $req->created_at;
                                @endphp
                                <tr>
                                    <td><span class="text-sm text-secondary-foreground">#{{ $req->id }}</span></td>
                                    <td><span class="text-sm font-medium text-mono">{{ $req->staff?->full_name ?? '—' }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $req->staff?->department?->name ?? '—' }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $req->branch?->name ?? '—' }}</span></td>
                                    <td><span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ ucfirst($req->type) }}</span></td>
                                    <td><span class="text-sm font-semibold text-mono">{{ $req->currency?->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}</span></td>
                                    <td><span class="sgh-badge sgh-badge-sm {{ $statusClass }}">{{ ucwords(str_replace('_', ' ', $req->status)) }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $dateValue?->format('d M Y') ?? '—' }}</span></td>
                                    <td>
                                        <a href="{{ route('payment-requests.show', $req) }}" class="sgh-btn sgh-btn-xs sgh-btn-light">
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
                <div class="sgh-card-footer py-4 px-5 lg:px-7.5">
                    {{ $rows->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
