@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('roles.clone_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('roles.clone_subtitle', ['name' => $role->name, 'count' => $role->permissions_count]) }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('roles.index') }}">
                <x-tabler-arrow-left />
                {{ __('roles.back') }}
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
                <h3 class="sgh-card-title">{{ __('roles.details_card') }}</h3>
            </div>
            <div class="sgh-card-content">
                <form method="POST" action="{{ route('roles.clone.store', $role) }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <!-- Role Name -->
                        <div>
                            <label class="sgh-form-label block mb-2" for="name">
                                {{ __('roles.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', __('roles.clone_default_name', ['name' => $role->name])) }}" class="sgh-input w-full" required aria-invalid="@error('name') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('roles.fields.clone_name_hint') }}</p>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="sgh-btn sgh-btn-primary">
                            <x-tabler-copy />
                            {{ __('roles.buttons.clone') }}
                        </button>
                        <a class="sgh-btn sgh-btn-light" href="{{ route('roles.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
