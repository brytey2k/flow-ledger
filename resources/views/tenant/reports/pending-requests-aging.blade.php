@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Pending Requests Aging</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Pending Requests Aging</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Requests currently waiting in a workflow stage, grouped by how long they have been sitting there.
                This report flags stuck approvals before they become a problem.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.pending-requests-aging'])
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Bucket summary --}}
        @php
            $bucketDefs = [
                '0–3 days'  => ['color' => 'sgh-badge-success', 'bg' => 'bg-success/10', 'text' => 'text-success'],
                '4–7 days'  => ['color' => 'sgh-badge-warning', 'bg' => 'bg-warning/10', 'text' => 'text-warning'],
                '8–14 days' => ['color' => 'sgh-badge-danger',  'bg' => 'bg-danger/10',  'text' => 'text-danger'],
                '15+ days'  => ['color' => 'sgh-badge-danger',  'bg' => 'bg-danger/10',  'text' => 'text-danger'],
            ];
        @endphp
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($bucketDefs as $label => $style)
                <div class="sgh-card p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-secondary-foreground">{{ $label }}</span>
                        <span class="sgh-badge sgh-badge-sm {{ $style['color'] }}">Waiting</span>
                    </div>
                    <div class="text-2xl font-semibold {{ $style['text'] }}">
                        {{ $bucketCounts->get($label, 0) }}
                    </div>
                    <div class="text-xs text-secondary-foreground mt-1">pending stages</div>
                </div>
            @endforeach
        </div>

        {{-- Detail table --}}
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">All Pending Stages</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $activeStages->count() }} active</span>
            </div>

            @if($activeStages->isEmpty())
                <div class="sgh-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-shield-check-filled class="text-5xl text-success mb-3" />
                    <p class="text-sm font-medium text-foreground">No pending approvals.</p>
                    <p class="text-xs text-secondary-foreground mt-1">All requests have been actioned.</p>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Request</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Type</span></span></th>
                                    <th class="min-w-[150px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Staff</span></span></th>
                                    <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Branch</span></span></th>
                                    <th class="min-w-[160px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Stage</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Waiting Since</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Days Waiting</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Aging</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeStages as $row)
                                    @php
                                        $stage = $row['stage'];
                                        $workflowable = $stage->instance?->workflowable;
                                        $isRetirement = $workflowable instanceof \App\Models\Tenant\RetirementRequest;
                                        $bucketColor = match($row['bucket']) {
                                            '0–3 days'  => 'sgh-badge-success',
                                            '4–7 days'  => 'sgh-badge-warning',
                                            default     => 'sgh-badge-danger',
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $workflowable?->id ?? '—' }}</span></td>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm {{ $isRetirement ? 'sgh-badge-success' : 'sgh-badge-primary' }}">
                                                {{ $isRetirement ? 'Retirement' : ucfirst($workflowable?->type ?? 'Request') }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm font-medium text-mono">{{ $workflowable?->staff?->full_name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $workflowable?->branch?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $stage->stage?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $stage->started_at?->format('M d, Y') ?? '—' }}</span></td>
                                        <td><span class="text-sm font-semibold {{ $row['days'] > 7 ? 'text-danger' : 'text-foreground' }}">{{ $row['days'] }}</span></td>
                                        <td><span class="sgh-badge sgh-badge-sm {{ $bucketColor }}">{{ $row['bucket'] }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
