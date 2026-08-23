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

@section('title', 'Roles — ' . $tenantName)

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Roles
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                <a href="{{ route('landlord.tenants.index') }}" class="hover:text-primary">Tenants</a>
                <span>/</span>
                <span>{{ $tenantName }}</span>
                <span>/</span>
                <span>Roles</span>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('landlord.tenants.users-permissions.index', $tenant) }}">
                <x-tabler-users-group />
                Manage Users
            </a>
            <a class="sgh-btn sgh-btn-outline" href="{{ route('landlord.tenants.index') }}">
                <x-tabler-arrow-left />
                Back to Tenants
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
                    Roles for {{ $tenantName }}
                </h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $roles->count() }} {{ Str::plural('Role', $roles->count()) }}
                    </span>
                </div>
            </div>
            <div class="sgh-card-content">
                @if($roles->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-shield-check-filled class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">No roles found</h3>
                        <p class="text-sm text-secondary-foreground">This tenant has no roles yet.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="sgh-table sgh-table-border table-fixed">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">Name</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">Users</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">Permissions</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">Action</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($roles as $role)
                                    <tr>
                                        <td>
                                            <span class="text-sm font-medium leading-none text-mono">{{ $role->name }}</span>
                                        </td>
                                        <td>
                                            <span class="badge badge-sm badge-outline">
                                                {{ $role->users_count }} {{ Str::plural('User', $role->users_count) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-sm badge-primary">
                                                {{ $role->permissions_count }} {{ Str::plural('Permission', $role->permissions_count) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('landlord.tenants.roles.permissions.edit', [$tenant, $role]) }}" class="sgh-btn sgh-btn-sm sgh-btn-outline">
                                                <x-tabler-edit-filled />
                                                Manage Permissions
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
