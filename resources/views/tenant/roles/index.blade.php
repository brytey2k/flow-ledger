@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('roles.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('roles.subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            @can(\App\Enums\Tenant\PermissionKey::CreateRole->value)
                <a class="kt-btn kt-btn-primary" href="{{ route('roles.create') }}">
                    <i class="ki-filled ki-plus"></i>
                    {{ __('roles.add_new') }}
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
                <h3 class="kt-card-title">{{ __('roles.all') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $roles->count() }} {{ Str::plural('Role', $roles->count()) }}
                    </span>
                </div>
            </div>

            @if($roles->isEmpty())
                <div class="kt-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-security-user text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('roles.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('roles.empty.subtext') }}</p>
                        @can(\App\Enums\Tenant\PermissionKey::CreateRole->value)
                            <a href="{{ route('roles.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                {{ __('roles.buttons.add') }}
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
                                            <span class="kt-table-col-label">{{ __('roles.fields.name') }}</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[120px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('roles.columns.users') }}</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('roles.columns.permissions') }}</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.created') }}</span>
                                            <span class="kt-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="kt-table-col">
                                            <span class="kt-table-col-label">{{ __('common.columns.actions') }}</span>
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
                                                {{ $role->users_count }} {{ Str::plural('user', $role->users_count) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-sm badge-primary">
                                                {{ $role->permissions_count }} {{ Str::plural('permission', $role->permissions_count) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm text-foreground">{{ $role->created_at->format('M d, Y') }}</span>
                                                <span class="text-2sm text-secondary-foreground">{{ $role->created_at->format('g:i A') }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                @can(\App\Enums\Tenant\PermissionKey::AccessRoles->value)
                                                    <a href="{{ route('roles.edit', $role) }}" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-primary" title="{{ __('common.edit') }}">
                                                        <i class="ki-filled ki-notepad-edit text-lg"></i>
                                                    </a>
                                                @endcan
                                                @can(\App\Enums\Tenant\PermissionKey::DeleteRole->value)
                                                    <form action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('{{ __('roles.confirm_delete') }}');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="kt-btn kt-btn-sm kt-btn-icon kt-btn-ghost text-danger" title="{{ __('common.delete') }}">
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
