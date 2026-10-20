@extends('landlord.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-col gap-2 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Platform overview</h1>
        <p class="text-sm text-secondary-foreground">Every customer's workload, stuck work and backup status in one place. Readings are refreshed every 10 minutes{{ $oldest_reading_at ? ' (oldest: ' . $oldest_reading_at->diffForHumans() . ')' : '' }}.</p>
        <form method="POST" action="{{ route('landlord.overview.refresh') }}">
            @csrf
            <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-outline">Refresh now</button>
        </form>
    </div>
</div>

@php
    $tiles = [
        ['Customers', $totals['tenants']],
        ['Needing attention', $totals['needing_attention']],
        ['Failed jobs', $totals['failed_jobs']],
        ['Blocked workflow stages', $totals['blocked_stages']],
    ];
    $ago = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->diffForHumans() : 'Never';
@endphp
<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        @if(! $heartbeat['ok'])
            <div class="sgh-card">
                <div class="sgh-card-header"><h3 class="sgh-card-title text-destructive">Background processing</h3></div>
                <div class="sgh-card-content grid gap-1 text-sm">
                    @foreach(['scheduler' => 'Scheduler', 'queue' => 'Queue workers'] as $key => $label)
                        @unless($heartbeat[$key]['ok'])
                            <span>{{ $label }}: {{ $heartbeat[$key]['last_seen_at'] ? 'last seen ' . \Illuminate\Support\Carbon::createFromTimestamp($heartbeat[$key]['last_seen_at'])->diffForHumans() : 'not seen yet' }}.</span>
                        @endunless
                    @endforeach
                </div>
            </div>
        @endif

        @if($backup_problems !== [])
            <div class="sgh-card">
                <div class="sgh-card-header"><h3 class="sgh-card-title text-destructive">Backup problems</h3></div>
                <div class="sgh-card-content grid gap-1 text-sm">
                    @foreach($backup_problems as $problem)<span>{{ $problem }}</span>@endforeach
                </div>
            </div>
        @endif

        <div class="grid gap-5 sm:grid-cols-2">
            @foreach($tiles as [$label, $value])
                <div class="sgh-card">
                    <div class="sgh-card-content flex flex-col gap-1">
                        <span class="text-2sm text-secondary-foreground">{{ $label }}</span>
                        <span class="text-2xl font-semibold text-mono">{{ number_format($value) }}</span>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="sgh-card">
            <div class="sgh-card-table">
                <div class="sgh-scrollable-x-auto">
                <table class="sgh-table sgh-table-border">
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th>Status</th>
                            <th>Last activity</th>
                            <th>Last backup</th>
                            <th>Last restore test</th>
                            <th>Needs attention</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tenants as $row)
                            @php($tenant = $row['tenant'])
                            @php($reading = $row['reading'])
                            @php($snapshot = $reading?->snapshot)
                            <tr>
                                <td class="font-medium text-mono">{{ trim((string) ($tenant->data['name'] ?? '')) ?: $tenant->id }}<div class="text-2sm font-normal text-muted-foreground">{{ $tenant->id }}</div></td>
                                <td><span class="sgh-badge sgh-badge-sm {{ $tenant->is_suspended ? 'sgh-badge-warning' : 'sgh-badge-success' }}">{{ $tenant->is_suspended ? 'Suspended' : 'Active' }}</span></td>
                                <td>{{ $snapshot ? $ago($snapshot['last_activity_at'] ?? null) : '—' }}</td>
                                <td>{{ $snapshot ? $ago($snapshot['last_backup_at'] ?? null) : '—' }}</td>
                                <td>{{ $snapshot ? $ago($snapshot['last_backup_verified_at'] ?? null) : '—' }}</td>
                                <td>
                                    @if($reading === null)
                                        <span class="text-muted-foreground">Not read yet</span>
                                    @else
                                        @forelse($reading->attention as $reason)
                                            <div class="text-destructive">{{ $reason }}</div>
                                        @empty
                                            <span class="text-muted-foreground">All clear</span>
                                        @endforelse
                                        <div class="text-2sm text-muted-foreground">Read {{ $reading->captured_at->diffForHumans() }}</div>
                                    @endif
                                </td>
                                <td class="text-end"><a class="sgh-btn sgh-btn-sm sgh-btn-outline" href="{{ route('landlord.tenants.health.index', $tenant) }}">Details</a></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-center text-muted-foreground">No customers yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
