@extends('landlord.layouts.base')

@section('content')
@include('landlord.tenants.partials.admin-nav')
<div class="sgh-container-fixed">
    <div class="flex flex-col gap-2 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Health</h1>
        <p class="text-sm text-secondary-foreground">A snapshot of this tenant's workload, stuck work and storage.</p>
    </div>
</div>

@php
    $stat = fn (string $label, $value, ?string $hint = null) => compact('label', 'value', 'hint');
    $bytes = $snapshot['storage_bytes'];
    $size = $bytes >= 1048576 ? round($bytes / 1048576, 2) . ' MB' : round($bytes / 1024, 1) . ' KB';
    $tiles = [
        $stat('Blocked workflow stages', $snapshot['blocked_stages'], 'Need a recovery action'),
        $stat('Stale workflow stages', $snapshot['stale_stages'], "Active for more than {$staleDays} days"),
        $stat('Overdue retirements', $snapshot['overdue_retirements'], 'Disbursed advances past the grace period'),
        $stat('Failed jobs', $snapshot['failed_jobs']),
        $stat('Attachments', number_format($snapshot['attachments']), $size),
        $stat('Last activity', $snapshot['last_activity_at'] ? \Illuminate\Support\Carbon::parse($snapshot['last_activity_at'])->diffForHumans() : 'Never'),
    ];
    $groups = ['Payment requests' => $snapshot['payment_requests'], 'Retirements' => $snapshot['retirements'], 'Users' => $snapshot['users']];
@endphp
<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($tiles as $tile)
                <div class="sgh-card">
                    <div class="sgh-card-content flex flex-col gap-1">
                        <span class="text-2sm text-secondary-foreground">{{ $tile['label'] }}</span>
                        <span class="text-2xl font-semibold text-mono">{{ $tile['value'] }}</span>
                        @if($tile['hint'])<span class="text-2sm text-muted-foreground">{{ $tile['hint'] }}</span>@endif
                    </div>
                </div>
            @endforeach
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            @foreach($groups as $title => $counts)
                <div class="sgh-card">
                    <div class="sgh-card-header"><h3 class="sgh-card-title">{{ $title }}{{ $title === 'Users' ? '' : ' by status' }}</h3></div>
                    <div class="sgh-card-content grid gap-2 text-sm">
                        @forelse($counts as $status => $total)
                            <div class="flex justify-between"><span>{{ \Illuminate\Support\Str::headline($status) }}</span><span class="font-medium">{{ number_format($total) }}</span></div>
                        @empty
                            <span class="text-muted-foreground">None.</span>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection
