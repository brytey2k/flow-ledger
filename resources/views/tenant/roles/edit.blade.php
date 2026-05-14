@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('roles.edit_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('roles.edit_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('roles.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('roles.back') }}
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
                <h3 class="kt-card-title">{{ __('roles.details_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <form id="update-role-form" method="POST" action="{{ route('roles.update', $role) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-5">
                        <div>
                            <label class="kt-form-label block mb-2" for="name">
                                {{ __('roles.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $role->name) }}" class="kt-input w-full" placeholder="e.g. Administrator" required aria-invalid="@error('name') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('roles.fields.name_hint') }}</p>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </form>

                @can(\App\Enums\Tenant\PermissionKey::DeleteRole->value)
                    <form id="delete-role-form" action="{{ route('roles.destroy', $role) }}" method="POST" onsubmit="return confirm('{{ __('roles.confirm_delete') }}');">
                        @csrf
                        @method('DELETE')
                    </form>
                @endcan

                <div class="pt-5 mt-2 flex justify-between items-center gap-2.5">
                    <div class="flex items-center gap-2.5">
                        <button type="submit" form="update-role-form" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check"></i>
                            {{ __('roles.buttons.update') }}
                        </button>
                        <a class="kt-btn kt-btn-outline" href="{{ route('roles.permissions.edit', $role) }}">
                            <i class="ki-filled ki-security-user"></i>
                            {{ __('roles.buttons.manage_perms') }}
                        </a>
                        <a class="kt-btn kt-btn-light" href="{{ route('roles.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                    @can(\App\Enums\Tenant\PermissionKey::DeleteRole->value)
                        <button type="submit" form="delete-role-form" class="kt-btn kt-btn-danger">
                            <i class="ki-filled ki-trash"></i>
                            {{ __('roles.buttons.delete') }}
                        </button>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
