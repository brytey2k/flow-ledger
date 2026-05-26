@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ $user->name }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                <span class="text-sm text-muted-foreground">{{ $user->email }}</span>
                @if($user->branch)
                    <span>&bull;</span>
                    <span class="text-sm text-muted-foreground">{{ $user->branch->name }}</span>
                @endif
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            @can(App\Enums\Tenant\PermissionKey::AccessUsers->value)
                <a class="kt-btn kt-btn-outline" href="{{ route('users.edit', $user) }}">
                    <i class="ki-filled ki-pencil"></i>
                    {{ __('users.edit_title') }}
                </a>
            @endcan

            @can(App\Enums\Tenant\PermissionKey::DeleteUser->value)
                <button type="button" class="kt-btn kt-btn-danger" onclick="if(confirm('{{ __('users.confirm_delete') }}')) { document.getElementById('delete-user-form').submit(); }">
                    <i class="ki-filled ki-trash"></i>
                    {{ __('users.buttons.delete') }}
                </button>
            @endcan

            <a class="kt-btn kt-btn-outline" href="{{ route('users.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('common.back') }}
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">{{ __('users.details_card') ?? 'User Details' }}</h3>
        </div>
        <div class="kt-card-content grid gap-4 p-5 lg:p-7.5">
            <div>
                <p class="text-sm text-muted-foreground">{{ __('common.columns.name') }}</p>
                <p class="text-lg font-medium text-mono">{{ $user->first_name }} {{ $user->last_name }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('common.columns.email') }}</p>
                <p class="text-sm text-foreground">{{ $user->email }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">Branch</p>
                <p class="text-sm text-foreground">{{ $user->branch->name ?? '-' }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('users.columns.roles') }}</p>
                <div class="flex flex-wrap gap-2 mt-1">
                    @forelse($user->roles as $role)
                        <span class="badge badge-sm badge-primary">{{ $role->name }}</span>
                    @empty
                        <span class="text-2sm text-muted-foreground">{{ __('users.columns.no_roles') }}</span>
                    @endforelse
                </div>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">Linked Staff Profile</p>
                @if($user->staffProfile)
                    <a href="{{ route('staff.show', $user->staffProfile) }}" class="text-sm font-medium text-primary hover:underline">{{ $user->staffProfile->full_name ?? $user->staffProfile->first_name . ' ' . $user->staffProfile->last_name }}</a>
                @else
                    <p class="text-sm text-muted-foreground">No staff profile linked.</p>
                @endif
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('common.columns.created') }}</p>
                <p class="text-sm text-foreground">{{ $user->created_at->format('M d, Y h:i A') }}</p>
            </div>
        </div>
            @can(App\Enums\Tenant\PermissionKey::DeleteUser->value)
                <form id="delete-user-form" action="{{ route('users.destroy', $user) }}" method="POST" class="hidden">
                    @csrf @method('DELETE')
                </form>
            @endcan
    </div>
    </div>
</div>
@endsection
