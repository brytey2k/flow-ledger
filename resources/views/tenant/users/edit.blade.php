@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('users.edit_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('users.edit_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('users.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('users.back') }}
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
                <h3 class="kt-card-title">{{ __('users.details_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <form id="edit-user-form" method="POST" action="{{ route('users.update', $user) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <!-- First Name -->
                        <div>
                            <label class="kt-form-label block mb-2" for="first_name">
                                {{ __('users.fields.first_name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="first_name" name="first_name" type="text" value="{{ old('first_name', $user->first_name) }}" class="kt-input w-full" placeholder="e.g. John" required aria-invalid="@error('first_name') true @else false @enderror" />
                            @error('first_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label class="kt-form-label block mb-2" for="last_name">
                                {{ __('users.fields.last_name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="last_name" name="last_name" type="text" value="{{ old('last_name', $user->last_name) }}" class="kt-input w-full" placeholder="e.g. Doe" required aria-invalid="@error('last_name') true @else false @enderror" />
                            @error('last_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="kt-form-label block mb-2" for="email">
                                {{ __('users.fields.email') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" class="kt-input w-full" required aria-invalid="@error('email') true @else false @enderror" />
                            @error('email')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div>
                            <label class="kt-form-label block mb-2" for="password">{{ __('users.fields.password') }}</label>
                            <input id="password" name="password" type="password" class="kt-input w-full" aria-invalid="@error('password') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('users.fields.password_hint') }}</p>
                            @error('password')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Password Confirmation -->
                        <div>
                            <label class="kt-form-label block mb-2" for="password_confirmation">{{ __('users.fields.confirm_password') }}</label>
                            <input id="password_confirmation" name="password_confirmation" type="password" class="kt-input w-full" />
                        </div>

                        <!-- Roles -->
                        <div class="col-span-1 lg:col-span-2">
                            <label class="kt-form-label block mb-2">{{ __('users.fields.roles') }}</label>
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
                        <button type="submit" form="edit-user-form" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check"></i>
                            {{ __('users.buttons.update') }}
                        </button>
                        <a class="kt-btn kt-btn-outline" href="{{ route('users.permissions.edit', $user) }}">
                            <i class="ki-filled ki-security-user"></i>
                            {{ __('users.buttons.manage_perms') }}
                        </a>
                        <a class="kt-btn kt-btn-light" href="{{ route('users.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                    @can(\App\Enums\Tenant\PermissionKey::DeleteUser->value)
                        <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('{{ __('users.confirm_delete') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="kt-btn kt-btn-danger">
                                <i class="ki-filled ki-trash"></i>
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
