@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('users.permissions.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('users.permissions.subtitle', ['name' => $user->name]) }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('users.edit', $user) }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('users.permissions.back') }}
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
                <h3 class="kt-card-title">{{ __('users.permissions.card') }}</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('users.permissions.update', $user) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="p-4 rounded-lg bg-muted/50">
                        <p class="text-sm text-secondary-foreground">
                            <i class="ki-filled ki-information-2"></i>
                            {{ __('users.permissions.description') }}
                        </p>
                    </div>

                    @include('partials.permission-selector', [
                        'permissions' => $permissions,
                        'selectedIds' => old('permissions', $user->permissions->pluck('id')->toArray()),
                    ])

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5 border-t">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-check"></i>
                            {{ __('users.permissions.update') }}
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('users.edit', $user) }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
