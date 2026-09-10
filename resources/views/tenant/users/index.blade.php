@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('users.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('users.subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            @can(\App\Enums\Tenant\PermissionKey::CreateUser->value)
                <a class="sgh-btn sgh-btn-primary" href="{{ route('users.create') }}">
                    <x-tabler-plus-filled />
                    {{ __('users.add_new') }}
                </a>
            @endcan
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="sgh-container-fixed">
    <x-index-filters :action="route('users.index')" :reset-url="route('users.index')" :filters="$filters" search-placeholder="Search user names or email…">
        <div class="flex flex-col gap-1">
            <label for="status" class="sgh-form-label">{{ __('common.columns.status') }}</label>
            <select id="status" name="status" class="sgh-select w-full">
                <option value="">{{ __('common.all_statuses') }}</option>
                @foreach(\App\Enums\Tenant\UserStatus::cases() as $status)
                    <option value="{{ $status->value }}" @selected(($filters['status'] ?? null) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label for="role_id" class="sgh-form-label">{{ __('users.columns.roles') }}</label>
            <select id="role_id" name="role_id" class="sgh-select w-full">
                <option value="">{{ __('common.all') }}</option>
                @foreach($roles as $role)
                    <option value="{{ $role->id }}" @selected(($filters['role_id'] ?? null) === $role->id)>{{ Str::headline($role->name) }}</option>
                @endforeach
            </select>
        </div>
    </x-index-filters>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('users.all') }}</h3>
                <div class="flex items-center gap-2">
                    <span class="badge badge-sm badge-outline">
                        {{ $users->count() }} {{ Str::plural('User', $users->count()) }}
                    </span>
                </div>
            </div>

            @if($users->isEmpty())
                <div class="sgh-card-content flex flex-col gap-4 p-5 lg:p-7.5 lg:pt-4">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-user-square-rounded class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('users.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('users.empty.subtext') }}</p>
                        @can(\App\Enums\Tenant\PermissionKey::CreateUser->value)
                            <a href="{{ route('users.create') }}" class="sgh-btn sgh-btn-primary">
                                <x-tabler-plus-filled />
                                {{ __('users.buttons.add') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.name') }}</span>
                                            <span class="sgh-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.email') }}</span>
                                            <span class="sgh-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[200px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('users.columns.roles') }}</span>
                                            <span class="sgh-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[110px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('users.columns.status') }}</span>
                                            <span class="sgh-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[150px]">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.created') }}</span>
                                            <span class="sgh-table-col-sort"></span>
                                        </span>
                                    </th>
                                    <th class="min-w-[100px] text-center">
                                        <span class="sgh-table-col">
                                            <span class="sgh-table-col-label">{{ __('common.columns.actions') }}</span>
                                        </span>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($users as $user)
                                    <tr>
                                        <td>
                                            <a href="{{ route('users.show', $user) }}" class="text-sm font-medium text-primary hover:underline">{{ $user->first_name }} {{ $user->last_name }}</a>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $user->email }}</span>
                                        </td>
                                        <td>
                                            <div class="flex flex-wrap items-center gap-1.5">
                                                @forelse($user->roles as $role)
                                                    <span class="sgh-badge sgh-badge-sm sgh-badge-primary">{{ Str::headline($role->name) }}</span>
                                                @empty
                                                    <span class="text-2sm text-muted-foreground">{{ __('users.columns.no_roles') }}</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            @if($user->status === \App\Enums\Tenant\UserStatus::Invited)
                                                <span class="badge badge-sm badge-warning">{{ $user->status->label() }}</span>
                                            @elseif($user->status === \App\Enums\Tenant\UserStatus::Suspended)
                                                <span class="badge badge-sm badge-destructive">{{ $user->status->label() }}</span>
                                            @else
                                                <span class="badge badge-sm badge-success">{{ $user->status->label() }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-sm text-foreground">{{ $user->created_at->format('M d, Y') }}</span>
                                                <span class="text-2sm text-secondary-foreground">{{ $user->created_at->format('g:i A') }}</span>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="flex items-center justify-center gap-2">
                                                @if($user->status === \App\Enums\Tenant\UserStatus::Invited)
                                                    @can(\App\Enums\Tenant\PermissionKey::CreateUser->value)
                                                        <form action="{{ $identityDelegated ? route('users.invite.resend', $user) : route('users.welcome.resend', $user) }}" method="POST" onsubmit="return confirm('{{ __('users.confirm_resend_invite') }}');" class="inline">
                                                            @csrf
                                                            <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-primary" title="{{ __('users.resend_invite') }}">
                                                                <x-tabler-message-circle-filled class="text-lg" />
                                                            </button>
                                                        </form>
                                                    @endcan
                                                @endif
                                                @can(\App\Enums\Tenant\PermissionKey::AccessUsers->value)
                                                    <a href="{{ route('users.edit', $user) }}" class="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-primary" title="{{ __('common.edit') }}">
                                                        <x-tabler-edit-filled class="text-lg" />
                                                    </a>
                                                @endcan
                                                @can(\App\Enums\Tenant\PermissionKey::DeleteUser->value)
                                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('{{ __('users.confirm_delete_short') }}');" class="inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-icon sgh-btn-ghost text-danger" title="{{ __('common.delete') }}">
                                                            <x-tabler-trash-filled class="text-lg" />
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
