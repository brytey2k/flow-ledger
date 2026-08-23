@extends('landlord.layouts.app')

@php
    $tenantName = trim((string) ($tenant->name ?? ''));
    if ($tenantName === '' && is_array($tenant->data ?? null)) {
        $tenantName = trim((string) ($tenant->data['name'] ?? ''));
    }
    if ($tenantName === '') {
        $tenantName = $tenant->id;
    }
@endphp

@section('title', 'User Permissions — ' . trim($user->first_name . ' ' . $user->last_name))

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Direct Permissions — {{ trim($user->first_name . ' ' . $user->last_name) }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                <a href="{{ route('landlord.tenants.index') }}" class="hover:text-primary">Tenants</a>
                <span>/</span>
                <a href="{{ route('landlord.tenants.users-permissions.index', $tenant) }}" class="hover:text-primary">{{ $tenantName }}</a>
                <span>/</span>
                <span>{{ $user->email }}</span>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('landlord.tenants.users-permissions.index', $tenant) }}">
                <x-tabler-arrow-left />
                Back to Users
            </a>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">
                    Manage Direct Permissions
                </h3>
            </div>
            <div class="sgh-card-content">
                <form method="POST" action="{{ route('landlord.tenants.users.permissions.update', [$tenant, $user]) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="mb-4 p-4 rounded-lg bg-warning/10 border border-warning/30">
                        <p class="text-sm text-warning">
                            <x-tabler-info-square-filled />
                            Changes made here bypass the tenant's own permission-escalation safeguard. Use this only to restore permissions the tenant admins are otherwise unable to grant themselves.
                        </p>
                    </div>

                    @error('permissions')
                        <div class="text-sm text-destructive">{{ $message }}</div>
                    @enderror

                    @include('partials.permission-selector', [
                        'permissions' => $permissions,
                        'selectedIds' => old('permissions', $user->permissions->pluck('id')->toArray()),
                    ])

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5 border-t">
                        <button type="submit" class="sgh-btn sgh-btn-primary">
                            <x-tabler-check-filled />
                            Update Permissions
                        </button>
                        <a class="sgh-btn sgh-btn-light" href="{{ route('landlord.tenants.users-permissions.index', $tenant) }}">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
