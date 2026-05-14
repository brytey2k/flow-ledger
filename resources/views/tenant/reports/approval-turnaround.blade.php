@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <i class="ki-filled ki-right text-xs"></i>
                <span>Approval Turnaround</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Approval Turnaround</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Average time from when a request reaches a stage to when it is acted on, broken down by workflow stage.
                High averages clearly show where bottlenecks are forming in your approval process.
            </p>
        </div>
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
                <button type="submit" class="kt-btn kt-btn-primary kt-btn-sm">Apply</button>
            </form>
        </div>

        {{-- Table --}}
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Turnaround by Stage</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $dateFrom }} — {{ $dateTo }}</span>
            </div>

            @if($stages->isEmpty())
                <div class="kt-card-content flex flex-col items-center justify-center py-12">
                    <i class="ki-filled ki-arrows-loop text-5xl text-muted-foreground mb-3"></i>
                    <p class="text-sm text-secondary-foreground">No completed stage data found for this period.</p>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[180px]"><span class="kt-table-col"><span class="kt-table-col-label">Stage</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Total Reviews</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Approved</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Sent Back</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Avg Time</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Fastest</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Slowest</span></span></th>
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
