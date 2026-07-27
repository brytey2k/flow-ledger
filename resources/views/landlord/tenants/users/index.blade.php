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

@section('title', 'Users — ' . $tenantName)

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">
                Users
            </h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                <a href="{{ route('landlord.tenants.index') }}" class="hover:text-primary">Tenants</a>
                <span>/</span>
                <span>{{ $tenantName }}</span>
                <span>/</span>
                <span>Users</span>
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('landlord.tenants.roles.index', $tenant) }}">
                <i class="ki-filled ki-security-user"></i>
                Manage Roles
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
                    Users for {{ $tenantName }}
                </h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $users->total() }} {{ Str::plural('User', $users->total()) }}
                    </span>
                </div>
            </div>
            <div class="kt-card-content">
                @if($users->isEmpty())
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-user text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No users found</h3>
                        <p class="text-sm text-secondary-foreground">This tenant has no staff users yet.</p>
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
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Email</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Roles</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Direct Permissions</span>
                                        </span>
                                    </th>
                                    <th class="min-w-[180px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Actions</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <span class="text-sm font-medium text-foreground">
                                                {{ trim($user->first_name . ' ' . $user->last_name) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-secondary-foreground">{{ $user->email }}</span>
                                        </td>
                                        <td>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @forelse($user->roles as $role)
                                                    <span class="badge badge-sm badge-outline">{{ $role->name }}</span>
                                                @empty
                                                    <span class="text-2sm text-muted-foreground">No roles</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge badge-sm badge-primary">
                                                {{ $user->permissions->count() }} {{ Str::plural('Permission', $user->permissions->count()) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                <a href="{{ route('landlord.tenants.users.roles.edit', [$tenant, $user]) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                                                    <i class="ki-filled ki-security-user"></i>
                                                    Roles
                                                </a>
                                                <a href="{{ route('landlord.tenants.users.permissions.edit', [$tenant, $user]) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                                                    <i class="ki-filled ki-notepad-edit"></i>
                                                    Permissions
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($users->hasPages())
                        <div class="flex justify-center pt-4">
                            {{ $users->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
