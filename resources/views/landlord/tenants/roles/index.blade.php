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
<div class="kt-container-fixed">
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
            <a class="kt-btn kt-btn-outline" href="{{ route('landlord.tenants.users-permissions.index', $tenant) }}">
                <i class="ki-filled ki-people"></i>
                Manage Users
            </a>
            <a class="kt-btn kt-btn-outline" href="{{ route('landlord.tenants.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                Back to Tenants
            </a>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Roles for {{ $tenantName }}
                </h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $roles->count() }} {{ Str::plural('Role', $roles->count()) }}
                    </span>
                </div>
            </div>
            <div class="kt-card-content">
                @if($roles->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-security-user text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No roles found</h3>
                        <p class="text-sm text-secondary-foreground">This tenant has no roles yet.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="kt-table kt-table-border table-fixed">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Name</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Users</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Permissions</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Action</span>
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
                                            <a href="{{ route('landlord.tenants.roles.permissions.edit', [$tenant, $role]) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                                                <i class="ki-filled ki-notepad-edit"></i>
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
