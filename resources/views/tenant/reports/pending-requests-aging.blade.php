@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>Pending Requests Aging</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Pending Requests Aging</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Requests currently waiting in a workflow stage, grouped by how long they have been sitting there.
                This report flags stuck approvals before they become a problem.
            </p>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Bucket summary --}}
        @php
            $bucketDefs = [
                '0–3 days'  => ['color' => 'kt-badge-success', 'bg' => 'bg-success/10', 'text' => 'text-success'],
                '4–7 days'  => ['color' => 'kt-badge-warning', 'bg' => 'bg-warning/10', 'text' => 'text-warning'],
                '8–14 days' => ['color' => 'kt-badge-danger',  'bg' => 'bg-danger/10',  'text' => 'text-danger'],
                '15+ days'  => ['color' => 'kt-badge-danger',  'bg' => 'bg-danger/10',  'text' => 'text-danger'],
            ];
        @endphp
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($bucketDefs as $label => $style)
                <div class="kt-card p-5">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm text-secondary-foreground">{{ $label }}</span>
                        <span class="kt-badge kt-badge-sm {{ $style['color'] }}">Waiting</span>
                    </div>
                    <div class="text-2xl font-semibold {{ $style['text'] }}">
                        {{ $bucketCounts->get($label, 0) }}
                    </div>
                    <div class="text-xs text-secondary-foreground mt-1">pending stages</div>
                </div>
            @endforeach
        </div>

        {{-- Detail table --}}
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">All Pending Stages</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $activeStages->count() }} active</span>
            </div>

            @if($activeStages->isEmpty())
                <div class="kt-card-content flex flex-col items-center justify-center py-12">
                    <i class="ki-filled ki-shield-tick text-5xl text-success mb-3"></i>
                    <p class="text-sm font-medium text-foreground">No pending approvals.</p>
                    <p class="text-xs text-secondary-foreground mt-1">All requests have been actioned.</p>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="kt-table-col"><span class="kt-table-col-label">Request</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Type</span></span></th>
                                    <th class="min-w-[150px]"><span class="kt-table-col"><span class="kt-table-col-label">Staff</span></span></th>
                                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">Branch</span></span></th>
                                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">Stage</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Waiting Since</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Days Waiting</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Aging</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($activeStages as $row)
                                    @php
                                        $stage = $row['stage'];
                                        $workflowable = $stage->instance?->workflowable;
                                        $isRetirement = $workflowable instanceof \App\Models\Tenant\RetirementRequest;
                                        $bucketColor = match($row['bucket']) {
                                            '0–3 days'  => 'kt-badge-success',
                                            '4–7 days'  => 'kt-badge-warning',
                                            default     => 'kt-badge-danger',
                                        };
                                    @endphp
                                    <tr>
                                        <td><span class="text-sm text-secondary-foreground">#{{ $workflowable?->id ?? '—' }}</span></td>
                                        <td>
                                            <span class="kt-badge kt-badge-sm {{ $isRetirement ? 'kt-badge-success' : 'kt-badge-primary' }}">
                                                {{ $isRetirement ? 'Retirement' : ucfirst($workflowable?->type ?? 'Request') }}
                                            </span>
                                        </td>
                                        <td><span class="text-sm font-medium text-mono">{{ $workflowable?->staff?->full_name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $workflowable?->branch?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $stage->stage?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $stage->started_at?->format('M d, Y') ?? '—' }}</span></td>
                                        <td><span class="text-sm font-semibold {{ $row['days'] > 7 ? 'text-danger' : 'text-foreground' }}">{{ $row['days'] }}</span></td>
                                        <td><span class="kt-badge kt-badge-sm {{ $bucketColor }}">{{ $row['bucket'] }}</span></td>
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
