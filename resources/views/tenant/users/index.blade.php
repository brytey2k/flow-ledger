@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Users Management</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Manage users and their roles
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            @can(\App\Enums\Tenant\PermissionKey::CreateUser->value)
                <a class="kt-btn kt-btn-primary" href="{{ route('users.create') }}">
                    <i class="ki-filled ki-plus"></i>
                    Add New User
                </a>
            @endcan
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">All Users</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $users->count() }} {{ Str::plural('User', $users->count()) }}
                    </span>
                </div>
            </div>

            @if($users->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-profile-user text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No users found</h3>
                        <p class="text-sm text-secondary-foreground mb-4">Get started by creating your first user</p>
                        @can(\App\Enums\Tenant\PermissionKey::CreateUser->value)
                            <a href="{{ route('users.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                Add User
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Name</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Email</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Roles</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">Created</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
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
                                            <span class="text-sm font-medium leading-none text-mono">{{ $user->first_name }} {{ $user->last_name }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $user->email }}</span>
                                        </td>
                                        <td>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @forelse($user->roles as $role)
                                                    <span class="badge badge-sm badge-primary">{{ $role->name }}</span>
                                                @empty
                                                    <span class="text-2sm text-muted-foreground">No roles</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm text-foreground">{{ $user->created_at->format('M d, Y') }}</span>
                                                <span class="text-2sm text-secondary-foreground">{{ $user->created_at->format('h:i A') }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                @can(\App\Enums\Tenant\PermissionKey::AccessUsers->value)
                                                    <a href="{{ route('users.edit', $user) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-primary" title="Edit">
                                                        <i class="ki-filled ki-notepad-edit text-lg"></i>
                                                    </a>
                                                @endcan
                                                @can(\App\Enums\Tenant\PermissionKey::DeleteUser->value)
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this user?');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-danger" title="Delete">
                                                            <i class="ki-filled ki-trash text-lg"></i>
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
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
<!-- End of Container -->
@endsection
