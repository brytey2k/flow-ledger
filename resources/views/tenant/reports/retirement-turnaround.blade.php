@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <div class="flex items-center gap-2 text-sm text-secondary-foreground">
                <a href="{{ route('reports.index') }}" class="hover:text-primary">Reports</a>
                <x-tabler-chevron-right-filled class="text-xs" />
                <span>Retirement Turnaround</span>
            </div>
            <h1 class="text-xl font-medium leading-none text-mono">Retirement Turnaround</h1>
            <p class="text-sm text-secondary-foreground max-w-2xl">
                Average time from when a retirement request reaches a workflow stage to when it is acted on.
                Shows where approval bottlenecks form in the retirement review process.
            </p>
        </div>
        @include('tenant.reports.partials.export-buttons', ['exportRoute' => 'reports.export.retirement-turnaround'])
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
                <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-primary">Apply</button>
                <a href="{{ route('reports.retirement-turnaround') }}" class="sgh-btn sgh-btn-sm sgh-btn-light">Reset</a>
            </form>
        </div>

        {{-- Table --}}
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Turnaround by Stage</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $stages->count() }} stages</span>
            </div>

            @if($stages->isEmpty())
                <div class="sgh-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-circle-check-filled class="text-3xl text-secondary-foreground mb-3" />
                    <p class="text-sm text-secondary-foreground">No completed retirement workflow stages in this period.</p>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th><span class="sgh-table-col"><span class="sgh-table-col-label">Stage</span></span></th>
                                    <th><span class="sgh-table-col sgh-table-col-end"><span class="sgh-table-col-label">Reviews</span></span></th>
                                    <th><span class="sgh-table-col sgh-table-col-end"><span class="sgh-table-col-label">Approved</span></span></th>
                                    <th><span class="sgh-table-col sgh-table-col-end"><span class="sgh-table-col-label">Sent Back</span></span></th>
                                    <th><span class="sgh-table-col sgh-table-col-end"><span class="sgh-table-col-label">Avg Hours</span></span></th>
                                    <th><span class="sgh-table-col sgh-table-col-end"><span class="sgh-table-col-label">Min Hours</span></span></th>
                                    <th><span class="sgh-table-col sgh-table-col-end"><span class="sgh-table-col-label">Max Hours</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $maxAvg = $stages->max('avg_hours'); @endphp
                                @foreach($stages as $stage)
                                    <tr>
                                        <td>
                                            <span class="text-sm font-medium">{{ $stage['stage_name'] }}</span>
                                        </td>
                                        <td class="text-right"><span class="text-sm font-semibold text-mono">{{ $stage['count'] }}</span></td>
                                        <td class="text-right">
                                            <span class="text-sm text-success font-medium">{{ $stage['approved'] }}</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-sm {{ $stage['sent_back'] > 0 ? 'text-warning font-medium' : 'text-secondary-foreground' }}">{{ $stage['sent_back'] }}</span>
                                        </td>
                                        <td class="text-right">
                                            <span class="text-sm font-semibold {{ $maxAvg > 0 && $stage['avg_hours'] >= $maxAvg * 0.8 ? 'text-danger' : 'text-mono' }}">
                                                {{ $stage['avg_hours'] }}h
                                            </span>
                                        </td>
                                        <td class="text-right"><span class="text-sm text-secondary-foreground">{{ $stage['min_hours'] }}h</span></td>
                                        <td class="text-right"><span class="text-sm text-secondary-foreground">{{ $stage['max_hours'] }}h</span></td>
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
