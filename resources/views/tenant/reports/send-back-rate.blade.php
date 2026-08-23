@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Send-Back Rate</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Send-Back Rate</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Percentage of reviews that result in a send-back, broken down per approver.
                High rates signal training gaps or poor submission quality and are a leading indicator of workflow inefficiency.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.send-back-rate'])
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
                <h3 class="sgh-card-title">Send-Back Rate by Approver</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $dateFrom }} — {{ $dateTo }}</span>
            </div>

            @if($rows->isEmpty())
                <div class="sgh-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-repeat class="text-5xl text-muted-foreground mb-3" />
                    <p class="text-sm text-secondary-foreground">No workflow actions recorded for this period.</p>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[180px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Approver</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Total Reviews</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Sent Back</span></span></th>
                                    <th class="min-w-[150px]"><span class="sgh-table-col"><span class="sgh-table-col-label">Send-Back Rate</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rows as $row)
                                    <tr>
                                        <td><span class="text-sm font-medium text-mono">{{ $row['user']?->name ?? '—' }}</span></td>
                                        <td><span class="text-sm text-foreground">{{ $row['total_actions'] }}</span></td>
                                        <td><span class="text-sm text-warning font-medium">{{ $row['sent_back_count'] }}</span></td>
                                        <td>
                                            <div class="flex items-center gap-2">
                                                <div class="h-1.5 w-24 rounded-full bg-muted overflow-hidden">
                                                    <div class="h-full rounded-full {{ $row['rate'] > 30 ? 'bg-danger' : ($row['rate'] > 15 ? 'bg-warning' : 'bg-success') }}"
                                                         style="width: {{ min($row['rate'], 100) }}%"></div>
                                                </div>
                                                <span class="text-sm font-semibold {{ $row['rate'] > 30 ? 'text-danger' : ($row['rate'] > 15 ? 'text-warning' : 'text-foreground') }}">
                                                    {{ $row['rate'] }}%
                                                </span>
                                            </div>
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
