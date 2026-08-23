@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('roles.permissions.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('roles.permissions.subtitle', ['role' => $role->name]) }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('roles.edit', $role) }}">
                <x-tabler-arrow-left />
                {{ __('roles.permissions.back') }}
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
                <h3 class="sgh-card-title">{{ __('roles.permissions.card') }}</h3>
            </div>
            <div class="sgh-card-content">
                <form method="POST" action="{{ route('roles.permissions.update', $role) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="p-4 rounded-lg bg-muted/50">
                        <p class="text-sm text-secondary-foreground">
                            <x-tabler-info-square-filled />
                            {{ __('roles.permissions.description') }}
                        </p>
                    </div>

                    @include('partials.permission-selector', [
                        'permissions' => $permissions,
                        'selectedIds' => old('permissions', $role->permissions->pluck('id')->toArray()),
                    ])

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5 border-t">
                        <button type="submit" class="sgh-btn sgh-btn-primary">
                            <x-tabler-check-filled />
                            {{ __('roles.permissions.update') }}
                        </button>
                        <a class="sgh-btn sgh-btn-light" href="{{ route('roles.edit', $role) }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
