@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
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

<div class="fl-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Filters --}}
        <div class="fl-card p-5">
            <form method="GET" class="flex flex-wrap gap-4 items-end">
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">From</label>
                    <input type="date" name="date_from" value="{{ $dateFrom }}" class="fl-input fl-input-sm" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <label class="text-xs font-medium text-secondary-foreground">To</label>
                    <input type="date" name="date_to" value="{{ $dateTo }}" class="fl-input fl-input-sm" />
                </div>
                <button type="submit" class="fl-btn fl-btn-sm fl-btn-primary">Apply</button>
                <a href="{{ route('reports.retirement-turnaround') }}" class="fl-btn fl-btn-sm fl-btn-light">Reset</a>
            </form>
        </div>

        {{-- Table --}}
        <div class="fl-card fl-card-grid">
            <div class="fl-card-header">
                <h3 class="fl-card-title">Turnaround by Stage</h3>
                <span class="fl-badge fl-badge-sm fl-badge-outline">{{ $stages->count() }} stages</span>
            </div>

            @if($stages->isEmpty())
                <div class="fl-card-content flex flex-col items-center justify-center py-12">
                    <x-tabler-circle-check-filled class="text-3xl text-secondary-foreground mb-3" />
                    <p class="text-sm text-secondary-foreground">No completed retirement workflow stages in this period.</p>
                </div>
            @else
                <div class="fl-card-table">
                    <div class="fl-scrollable-x-auto border-b border-border">
                        <table class="fl-table fl-table-border">
                            <thead>
                                <tr>
                                    <th><span class="fl-table-col"><span class="fl-table-col-label">Stage</span></span></th>
                                    <th><span class="fl-table-col fl-table-col-end"><span class="fl-table-col-label">Reviews</span></span></th>
                                    <th><span class="fl-table-col fl-table-col-end"><span class="fl-table-col-label">Approved</span></span></th>
                                    <th><span class="fl-table-col fl-table-col-end"><span class="fl-table-col-label">Sent Back</span></span></th>
                                    <th><span class="fl-table-col fl-table-col-end"><span class="fl-table-col-label">Avg Hours</span></span></th>
                                    <th><span class="fl-table-col fl-table-col-end"><span class="fl-table-col-label">Min Hours</span></span></th>
                                    <th><span class="fl-table-col fl-table-col-end"><span class="fl-table-col-label">Max Hours</span></span></th>
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
