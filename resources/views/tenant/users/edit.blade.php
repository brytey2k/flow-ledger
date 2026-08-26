@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('users.edit_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('users.edit_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('users.index') }}">
                <x-tabler-arrow-left />
                {{ __('users.back') }}
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
                <h3 class="sgh-card-title">{{ __('users.details_card') }}</h3>
            </div>
            <div class="sgh-card-content">
                <form id="edit-user-form" method="POST" action="{{ route('users.update', $user) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <!-- First Name -->
                        <div>
                            <label class="sgh-form-label block mb-2" for="first_name">
                                {{ __('users.fields.first_name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $user->first_name) }}" class="sgh-input w-full" placeholder="e.g. John" required aria-invalid="@error('first_name') true @else false @enderror" />
                            @error('first_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label class="sgh-form-label block mb-2" for="last_name">
                                {{ __('users.fields.last_name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $user->last_name) }}" class="sgh-input w-full" placeholder="e.g. Doe" required aria-invalid="@error('last_name') true @else false @enderror" />
                            @error('last_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="sgh-form-label block mb-2" for="email">
                                {{ __('users.fields.email') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="sgh-input w-full" required aria-invalid="@error('email') true @else false @enderror" />
                            @error('email')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Roles -->
                        <div class="col-span-1 lg:col-span-2">
                            <label class="sgh-form-label block mb-2">{{ __('users.fields.roles') }}</label>
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                @forelse($roles as $role)
                                    <div class="flex items-start gap-2">
                                        <input
                                            type="checkbox"
                                            id="role-{{ $role->id }}"
                                            name="roles[]"
                                            value="{{ $role->id }}"
                                            {{ in_array($role->id, old('roles', $user->roles->pluck('id')->toArray())) ? 'checked' : '' }}
                                            class="mt-1"
                                        />
                                        <label for="role-{{ $role->id }}" class="text-sm cursor-pointer">
                                            {{ $role->name }}
                                        </label>
                                    </div>
                                @empty
                                    <p class="text-sm text-muted-foreground col-span-full">{{ __('users.fields.no_roles') }}</p>
                                @endforelse
                            </div>
                            @error('roles')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                </form>

                <div class="pt-5 mt-2 flex justify-between items-center gap-2.5">
                    <div class="flex items-center gap-2.5">
                        <button type="submit" form="edit-user-form" class="sgh-btn sgh-btn-primary">
                            <x-tabler-check-filled />
                            {{ __('users.buttons.update') }}
                        </button>
                        @can(\App\Enums\Tenant\PermissionKey::ManageUserPermissions->value)
                            <a class="sgh-btn sgh-btn-outline" href="{{ route('users.permissions.edit', $user) }}">
                                <x-tabler-shield-check-filled />
                                {{ __('users.buttons.manage_perms') }}
                            </a>
                        @endcan
                        <a class="sgh-btn sgh-btn-light" href="{{ route('users.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                    @can(\App\Enums\Tenant\PermissionKey::DeleteUser->value)
                        <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('{{ __('users.confirm_delete') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sgh-btn sgh-btn-danger">
                                <x-tabler-trash-filled />
                                {{ __('users.buttons.delete') }}
                            </button>
                        </form>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
