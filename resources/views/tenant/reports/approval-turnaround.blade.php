@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Approval Turnaround</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Approval Turnaround</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Average time from when a request reaches a stage to when it is acted on, broken down by workflow stage.
                High averages clearly show where bottlenecks are forming in your approval process.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.approval-turnaround'])
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
                <button type="submit" class="sgh-btn sgh-btn-primary sgh-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Table --}}
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Turnaround by Stage</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $dateFrom }} — {{ $dateTo }}</span>
            </div>

            @if($stages->isEmpty())
                <div class="sgh-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-repeat class="text-5xl text-muted-foreground mb-3" />
                    <p class="text-sm text-secondary-foreground">No completed stage data found for this period.</p>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[180px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Stage</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Total Reviews</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Approved</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Sent Back</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Avg Time</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Fastest</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Slowest</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stages as $row)
                                    <tr>
                                        <td><span class="text-sm font-medium text-mono">{{ $row['stage_name'] }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $row['count'] }}</span></td>
                                        <td><span class="text-sm text-success font-medium">{{ $row['approved'] }}</span></td>
                                        <td><span class="text-sm text-warning font-medium">{{ $row['sent_back'] }}</span></td>
                                        <td>
                                            <span class="text-sm font-semibold text-mono {{ $row['avg_hours'] > 48 ? 'text-danger' : ($row['avg_hours'] > 24 ? 'text-warning' : 'text-foreground') }}">
                                                @if($row['avg_hours'] >= 24)
                                                    {{ round($row['avg_hours'] / 24, 1) }}d
                                                @else
                                                    {{ $row['avg_hours'] }}h
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-secondary-foreground">
                                                @if($row['min_hours'] >= 24)
                                                    {{ round($row['min_hours'] / 24, 1) }}d
                                                @else
                                                    {{ $row['min_hours'] }}h
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-secondary-foreground">
                                                @if($row['max_hours'] >= 24)
                                                    {{ round($row['max_hours'] / 24, 1) }}d
                                                @else
                                                    {{ $row['max_hours'] }}h
                                                @endif
                                            </span>
                                        </td>
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
