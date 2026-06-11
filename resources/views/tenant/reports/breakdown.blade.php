@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>{{ $title }}</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">{{ $title }}</h1>
            <p class="text-sm text-secondary-foreground">
                {{ $dateFrom }} — {{ $dateTo }}
            </p>
        </div>
        <div class="flex items-center gap-2">
            @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.breakdown'])
            <a href="javascript:history.back()" class="kt-btn kt-btn-light kt-btn-sm">
                <i class="ki-filled ki-arrow-left text-xs"></i> Back
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="kt-card kt-card-grid">
        <div class="kt-card-header">
            <h3 class="kt-card-title">Payment Requests</h3>
            <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $rows->total() }} records</span>
        </div>

        @if($rows->isEmpty())
            <div class="kt-card-content flex flex-col items-center justify-center py-12">
                <i class="ki-filled ki-document text-5xl text-muted-foreground mb-3"></i>
                <p class="text-sm text-secondary-foreground">No payment requests found.</p>
            </div>
        @else
            <div class="kt-card-table">
                <div class="kt-scrollable-x-auto border-b border-border">
                    <table class="kt-table kt-table-border">
                        <thead>
                            <tr>
                                <th class="min-w-[60px]"><span class="kt-table-col"><span class="kt-table-col-label">#</span></span></th>
                                <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Staff</span></span></th>
                                <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Department</span></span></th>
                                <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Branch</span></span></th>
                                <th class="min-w-[80px]"><span class="kt-table-col"><span class="kt-table-col-label">Type</span></span></th>
                                <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">Amount</span></span></th>
                                <th class="min-w-[110px]"><span class="kt-table-col"><span class="kt-table-col-label">Status</span></span></th>
                                <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Date</span></span></th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $req)
                                @php
                                    $statusColors = [
                                        'draft'       => 'kt-badge-outline',
                                        'in_workflow' => 'kt-badge-primary',
                                        'approved'    => 'kt-badge-success',
                                        'disbursed'   => 'kt-badge-info',
                                        'retired'     => 'kt-badge-neutral',
                                        'sent_back'   => 'kt-badge-warning',
                                        'cancelled'   => 'kt-badge-danger',
                                        'denied'      => 'kt-badge-danger',
                                        'settled'     => 'kt-badge-success',
                                    ];
                                    $statusClass = $statusColors[$req->status] ?? 'kt-badge-outline';
                                    $dateValue = $req->disbursed_at ?? $req->updated_at ?? $req->created_at;
                                @endphp
                                <tr>
                                    <td><span class="text-sm text-secondary-foreground">#{{ $req->id }}</span></td>
                                    <td><span class="text-sm font-medium text-mono">{{ $req->staff?->full_name ?? '—' }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $req->staff?->department?->name ?? '—' }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $req->branch?->name ?? '—' }}</span></td>
                                    <td><span class="kt-badge kt-badge-sm kt-badge-outline">{{ ucfirst($req->type) }}</span></td>
                                    <td><span class="text-sm font-semibold text-mono">{{ $req->currency?->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}</span></td>
                                    <td><span class="kt-badge kt-badge-sm {{ $statusClass }}">{{ ucwords(str_replace('_', ' ', $req->status)) }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $dateValue?->format('d M Y') ?? '—' }}</span></td>
                                    <td>
                                        <a href="{{ route('payment-requests.show', $req) }}" class="kt-btn kt-btn-xs kt-btn-light">
                                            View <i class="ki-filled ki-arrow-right text-xs"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            @if($rows->hasPages())
                <div class="kt-card-footer py-4 px-5 lg:px-7.5">
                    {{ $rows->links() }}
                </div>
            @endif
        @endif
    </div>
</div>
@endsection
