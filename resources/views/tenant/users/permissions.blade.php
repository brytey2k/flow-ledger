@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('users.permissions.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('users.permissions.subtitle', ['name' => $user->name]) }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="fl-btn fl-btn-outline" href="{{ route('users.edit', $user) }}">
                <x-tabler-arrow-left />
                {{ __('users.permissions.back') }}
            </a>
        </div>
    </div>
</div>
<!-- End of Container -->

<!-- Container -->
<div class="fl-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="fl-card">
            <div class="fl-card-header">
                <h3 class="fl-card-title">{{ __('users.permissions.card') }}</h3>
            </div>
            <div class="fl-card-content">
                <form method="POST" action="{{ route('users.permissions.update', $user) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="p-4 rounded-lg bg-muted/50">
                        <p class="text-sm text-secondary-foreground">
                            <x-tabler-info-square-filled />
                            {{ __('users.permissions.description') }}
                        </p>
                    </div>

                    @include('partials.permission-selector', [
                        'permissions' => $permissions,
                        'selectedIds' => old('permissions', $user->permissions->pluck('id')->toArray()),
                    ])

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5 border-t">
                        <button type="submit" class="fl-btn fl-btn-primary">
                            <x-tabler-check-filled />
                            {{ __('users.permissions.update') }}
                        </button>
                        <a class="fl-btn fl-btn-light" href="{{ route('users.edit', $user) }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
