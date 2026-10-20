@extends('landlord.layouts.base')

@section('content')
@include('landlord.tenants.partials.admin-nav')
<div class="sgh-container-fixed">
    <div class="flex flex-col gap-2 pb-7.5">
        <h1 class="text-xl font-medium leading-none text-mono">Failed Jobs</h1>
        <p class="text-sm text-secondary-foreground">Failed queue jobs for this tenant. Retry puts the job back on the queue; discard removes it.</p>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="sgh-card">
        <div class="sgh-card-content p-0">
            @if(count($jobs) === 0)
                <div class="p-8 text-center text-muted-foreground">No failed jobs.</div>
            @else
                <div class="divide-y">
                    @foreach($jobs as $job)
                        <div class="flex flex-wrap items-start justify-between gap-4 p-4 text-sm">
                            <div class="flex min-w-0 flex-col gap-1">
                                <span class="font-medium">{{ $job['job'] }}</span>
                                <span class="text-2sm text-secondary-foreground">{{ $job['failed_at'] }} &middot; queue {{ $job['queue'] }}</span>
                                <span class="break-words text-2sm text-danger">{{ $job['exception'] }}</span>
                            </div>
                            <div class="flex gap-2">
                                <form method="POST" action="{{ route('landlord.tenants.failed-jobs.retry', [$tenant, $job['scope'], $job['uuid']]) }}">
                                    @csrf
                                    <button class="sgh-btn sgh-btn-sm sgh-btn-primary" type="submit">Retry</button>
                                </form>
                                <form method="POST" action="{{ route('landlord.tenants.failed-jobs.discard', [$tenant, $job['scope'], $job['uuid']]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button class="sgh-btn sgh-btn-sm sgh-btn-light" type="submit">Discard</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
