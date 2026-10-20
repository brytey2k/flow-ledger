@extends('landlord.layouts.base')

@section('content')
@php($preview = session('preview'))
@include('landlord.tenants.partials.admin-nav')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div class="flex flex-col gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Data Tools: {{ $tenant->id }}</h1>
            <p class="text-sm text-secondary-foreground">
                Hard-delete or repair transactional data. Every run is recorded in the audit log. Preview first to see exactly what will be removed.
            </p>
        </div>
        <a href="{{ route('landlord.tenants.index') }}" class="sgh-btn sgh-btn-light">Back to tenants</a>
    </div>
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        @if($hasActiveRun)
            <div class="rounded-lg border border-warning/30 bg-warning/10 p-4 text-sm text-warning">
                A data tool is currently queued or running for this tenant. New runs are blocked until it finishes.
            </div>
        @endif

        @foreach($tools as $tool)
            <div class="sgh-card">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title {{ $tool->isDestructive() ? '' : 'text-mono' }}">{{ $tool->label() }}</h3>
                    <p class="sgh-card-subtitle">{{ $tool->description() }}</p>
                </div>
                <div class="sgh-card-content">
                    <form method="POST" action="{{ route('landlord.tenants.data-tools.run', [$tenant, $tool->value]) }}" class="grid gap-4">
                        @csrf

                        @if($tool->supportsDateRange())
                            <div class="flex flex-wrap gap-4">
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium" for="from_{{ $tool->value }}">From (inclusive)</label>
                                    <input id="from_{{ $tool->value }}" name="from" type="date" class="sgh-input" value="{{ old('from') }}">
                                </div>
                                <div class="flex flex-col gap-1.5">
                                    <label class="text-sm font-medium" for="to_{{ $tool->value }}">To (inclusive)</label>
                                    <input id="to_{{ $tool->value }}" name="to" type="date" class="sgh-input" value="{{ old('to') }}">
                                </div>
                            </div>
                        @endif

                        @if($tool->supportsStatusFilter())
                            <div class="flex flex-col gap-1.5">
                                <span class="text-sm font-medium">Only these statuses (leave empty for all)</span>
                                <div class="flex flex-wrap gap-4">
                                    @foreach($tool->statusOptions() as $status)
                                        <label class="flex items-center gap-2 text-sm">
                                            <input type="checkbox" name="statuses[]" value="{{ $status }}" class="sgh-checkbox" @checked(in_array($status, (array) old('statuses', []), true))>
                                            {{ \Illuminate\Support\Str::headline($status) }}
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        @if($tool->supportsKeepDrafts())
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="keep_drafts" value="1" class="sgh-checkbox" @checked(old('keep_drafts'))>
                                Keep drafts
                            </label>
                        @endif

                        @if($preview && $preview['tool'] === $tool->value)
                            <div class="rounded-lg border border-border bg-secondary/50 p-4 text-sm">
                                <div class="mb-2 font-medium">This would affect:</div>
                                @forelse($preview['counts'] as $label => $count)
                                    <div class="flex justify-between gap-4"><span>{{ \Illuminate\Support\Str::headline($label) }}</span><span class="font-medium">{{ number_format($count) }}</span></div>
                                @empty
                                    <div class="text-muted-foreground">Nothing.</div>
                                @endforelse
                            </div>
                        @endif

                        @if($tool->isDestructive())
                            <div class="grid gap-2">
                                <label class="sgh-form-label" for="confirm_{{ $tool->value }}">
                                    Type tenant name <span class="font-semibold text-foreground">{{ $tenantName }}</span> to confirm
                                </label>
                                <input id="confirm_{{ $tool->value }}" name="confirm_tenant_name" type="text" class="sgh-input" autocomplete="off">
                            </div>
                        @endif

                        <div class="flex gap-2">
                            <button type="submit" formaction="{{ route('landlord.tenants.data-tools.preview', [$tenant, $tool->value]) }}" class="sgh-btn sgh-btn-light">Preview</button>
                            <button type="submit" class="sgh-btn {{ $tool->isDestructive() ? 'sgh-btn-danger' : 'sgh-btn-primary' }}" @disabled($hasActiveRun)>
                                {{ $tool->isDestructive() ? 'Run (permanent)' : 'Run' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endforeach

        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">Run history</h3>
                <p class="sgh-card-subtitle">Most recent runs for this tenant. Refresh to see progress.</p>
            </div>
            <div class="sgh-card-content p-0">
                @if($recentRuns->isEmpty())
                    <div class="p-8 text-center text-muted-foreground">No runs yet.</div>
                @else
                    <div class="divide-y">
                        @foreach($recentRuns as $run)
                            <div class="flex flex-col gap-1 p-4 text-sm">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <span class="font-medium">{{ $run->label() }}</span>
                                    <span class="sgh-badge {{ match($run->status->value) { 'completed' => 'sgh-badge-success', 'failed' => 'sgh-badge-danger', default => 'sgh-badge-warning' } }}">{{ \Illuminate\Support\Str::headline($run->status->value) }}</span>
                                </div>
                                <span class="text-2sm text-secondary-foreground">
                                    {{ $run->landlord_user_email ?? 'Unknown user' }} &middot; {{ $run->created_at?->format('M d, Y g:i A') }}
                                </span>
                                @if($run->result)
                                    <span class="text-2sm">
                                        @foreach($run->result as $label => $count){{ \Illuminate\Support\Str::headline($label) }}: {{ number_format($count) }}@if(! $loop->last), @endif @endforeach
                                    </span>
                                @endif
                                @if($run->artifact_path)
                                    <a class="text-2sm text-primary underline" href="{{ route('landlord.tenants.data-tools.download', [$tenant, $run]) }}">Download export</a>
                                @endif
                                @if($run->error)
                                    <span class="text-2sm text-danger">{{ $run->error }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
