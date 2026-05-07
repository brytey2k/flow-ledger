@extends('landlord.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5">
        <div class="flex flex-col gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Feature Flags</h1>
            <p class="text-sm text-secondary-foreground">Manage features per tenant or apply bulk changes across all tenants.</p>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Bulk Actions --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Bulk Actions</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('landlord.feature-flags.bulk-update') }}" class="flex flex-wrap items-end gap-4">
                    @csrf
                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium">Feature</label>
                        <select name="flag" class="kt-select w-60" required>
                            @foreach($flagDefinitions as $flag)
                                <option value="{{ $flag->value }}">{{ $flag->label() }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" name="action" value="enable"
                                class="kt-btn kt-btn-primary">
                            Enable for All
                        </button>
                        <button type="submit" name="action" value="disable"
                                class="kt-btn kt-btn-light">
                            Disable for All
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Per-tenant --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Tenants</h3>
                <p class="kt-card-subtitle">Click a tenant to manage its feature flags individually.</p>
            </div>
            <div class="kt-card-content p-0">
                @if($tenants->isEmpty())
                    <div class="p-8 text-center text-muted-foreground">No tenants yet.</div>
                @else
                    <div class="divide-y">
                        @foreach($tenants as $tenant)
                            <div class="flex items-center justify-between gap-4 p-4 hover:bg-secondary/50">
                                <div class="flex flex-col gap-0.5">
                                    <span class="font-medium text-sm">{{ $tenant->id }}</span>
                                    <span class="text-xs text-muted-foreground">
                                        {{ $tenant->domains->first()?->domain ?? '—' }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    @if($tenant->isSuspended())
                                        <span class="kt-badge kt-badge-danger kt-badge-sm">Suspended</span>
                                    @else
                                        <span class="kt-badge kt-badge-success kt-badge-sm">Active</span>
                                    @endif
                                    <a href="{{ route('landlord.tenants.feature-flags.index', $tenant) }}"
                                       class="kt-btn kt-btn-sm kt-btn-light">
                                        Manage Flags
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
