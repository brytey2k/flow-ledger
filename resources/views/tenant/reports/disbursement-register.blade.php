@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
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

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Filters --}}
        <div class="sgh-card p-5">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="sgh-input sgh-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="sgh-input sgh-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Branch</label>
                    <select name="branch_id" class="sgh-select sgh-select-sm">
                        <option value="">All Branches</option>
                        @foreach($branches as $id => $name)
                            <option value="{{ $id }}" @selected((string) $branchId === (string) $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">Method</label>
                    <select name="method" class="sgh-select sgh-select-sm">
                        <option value="">All Methods</option>
                        @foreach($methods as $m)
                            <option value="{{ $m->value }}" @selected($method === $m->value)>{{ ucwords(str_replace('_', ' ', $m->value)) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="sgh-btn sgh-btn-primary sgh-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Table --}}
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Disbursements</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $disbursements->total() }} records</span>
            </div>

            @if($disbursements->isEmpty())
                <div class="sgh-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-building-bank class="text-5xl text-muted-foreground mb-3" />
                    <p class="text-sm text-secondary-foreground">No disbursements found for this period.</p>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">#</span></span></th>
                                    <th class="min-w-[150px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Staff</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Branch</span></span></th>
                                    <th class="min-w-[80px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Type</span></span></th>
                                    <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Amount</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Method</span></span></th>
                                    <th class="min-w-[140px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Reference</span></span></th>
                                    <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Disbursed By</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Date</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($disbursements as $req)
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $req->id }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $req->staff?->full_name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $req->branch?->name ?? '—' }}</span></td>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm {{ $req->type === 'advance' ? 'sgh-badge-primary' : 'sgh-badge-warning' }}">
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
                    <div class="sgh-card-footer py-4 px-5 lg:px-7.5">
                        {{ $disbursements->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</div>
@endsection
