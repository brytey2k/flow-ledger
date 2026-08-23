@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Audit Trail</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Audit Trail</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Full workflow action history — who approved, sent back, or cancelled, with timestamps and comments.
                Required for internal and external audits to demonstrate a clear chain of accountability.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.audit-trail'])
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
                    <label class="text-xs font-medium text-secondary-foreground">Action</label>
                    <select name="action" class="sgh-select sgh-select-sm">
                        <option value="">All Actions</option>
                        @foreach($actionTypes as $type)
                            <option value="{{ $type }}" @selected($action === $type)>{{ ucwords(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="sgh-btn sgh-btn-primary sgh-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Table --}}
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Workflow Actions</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $actions->total() }} records</span>
            </div>

            @if($actions->isEmpty())
                <div class="sgh-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-shield-filled class="text-5xl text-muted-foreground mb-3" />
                    <p class="text-sm text-secondary-foreground">No workflow actions found for this period.</p>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Request</span></span></th>
                                    <th class="min-w-[160px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Stage</span></span></th>
                                    <th class="min-w-[150px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Actioned By</span></span></th>
                                    <th class="min-w-[110px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Action</span></span></th>
                                    <th class="min-w-[220px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Comment</span></span></th>
                                    <th class="min-w-[140px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Date &amp; Time</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($actions as $wfAction)
                                    @php
                                        $actionColors = [
                                            'approved'   => 'sgh-badge-success',
                                            'sent_back'  => 'sgh-badge-warning',
                                            'cancelled'  => 'sgh-badge-danger',
                                        ];
                                        $workflowable = $wfAction->instanceStage?->instance?->workflowable;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="text-sm text-secondary-foreground">
                                                #{{ $workflowable?->id ?? '—' }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $wfAction->instanceStage?->stage?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $wfAction->user?->name ?? '—' }}</span></td>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm {{ $actionColors[$wfAction->action] ?? 'sgh-badge-outline' }}">
                                                {{ ucwords(str_replace('_', ' ', $wfAction->action)) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-secondary-foreground">
                                                {{ $wfAction->comment ? Str::limit($wfAction->comment, 80) : '—' }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm text-foreground">{{ $wfAction->created_at?->format('M d, Y g:i A') ?? '—' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($actions->hasPages())
                    <div class="sgh-card-footer py-4 px-5 lg:px-7.5">
                        {{ $actions->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</div>
@endsection
