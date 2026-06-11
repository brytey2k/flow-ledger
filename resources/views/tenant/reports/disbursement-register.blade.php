@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>Disbursement Register</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Disbursement Register</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                All disbursed payments in a date range: amount, method, reference, recipient, and who disbursed.
                This is the auditor's first ask and serves as the official payment register for the period.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.disbursement-register'])
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Filters --}}
        <div class="kt-card p-5">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="kt-input kt-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="kt-input kt-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Branch</label>
                    <select name="branch_id" class="kt-select kt-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" @selected((string) $branchId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Method</label>
                    <select name="method" class="kt-select kt-select-sm">
                        <option value="">All Methods</option>
                        @foreach($methods as $m)
                            <option value="{{ $m->value }}" @selected($method === $m->value)>{{ ucwords(str_replace('_', ' ', $m->value)) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Table --}}
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Disbursements</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $disbursements->total() }} records</span>
            </div>

            @if($disbursements->isEmpty())
                <div class="kt-card-content flex flex-col items-center justify-center py-12">
                    <i class="ki-filled ki-bank text-5xl text-muted-foreground mb-3"></i>
                    <p class="text-sm text-secondary-foreground">No disbursements found for this period.</p>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="kt-table-col"><span class="kt-table-col-label">#</span></span></th>
                                    <th class="min-w-[150px]"><span class="kt-table-col"><span class="kt-table-col-label">Staff</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Branch</span></span></th>
                                    <th class="min-w-[80px]"><span class="kt-table-col"><span class="kt-table-col-label">Type</span></span></th>
                                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">Amount</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Method</span></span></th>
                                    <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">Reference</span></span></th>
                                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">Disbursed By</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Date</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($disbursements as $req)
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $req->id }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $req->staff?->full_name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $req->branch?->name ?? '—' }}</span></td>
                                        <td>
                                            <span class="kt-badge kt-badge-sm {{ $req->type === 'advance' ? 'kt-badge-primary' : 'kt-badge-warning' }}">
                                                {{ ucfirst($req->type) }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm font-medium text-mono">{{ $req->currency?->symbol ?? '' }} {{ number_format((float) $req->total_amount, 2) }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $req->disbursement_method ? ucwords(str_replace('_', ' ', $req->disbursement_method->value)) : '—' }}</span></td>
                                        <td><span class="text-sm text-foreground font-mono">{{ $req->disbursement_reference ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $req->disbursedBy?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $req->disbursed_at?->format('M d, Y') ?? '—' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($disbursements->hasPages())
                    <div class="kt-card-footer py-4 px-5 lg:px-7.5">
                        {{ $disbursements->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</div>
@endsection
