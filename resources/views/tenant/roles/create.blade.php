@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('roles.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('roles.create_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="fl-btn fl-btn-outline" href="{{ route('roles.index') }}">
                <x-tabler-arrow-left />
                {{ __('roles.back') }}
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
                <h3 class="fl-card-title">{{ __('roles.details_card') }}</h3>
            </div>
            <div class="fl-card-content">
                <form method="POST" action="{{ route('roles.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <!-- Role Name -->
                        <div>
                            <label class="fl-form-label block mb-2" for="name">
                                {{ __('roles.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" class="fl-input w-full" placeholder="e.g. Administrator" required aria-invalid="@error('name') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('roles.fields.name_hint') }}</p>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Guard Name -->
                        <div>
                            <label class="fl-form-label block mb-2" for="guard_name">{{ __('roles.fields.guard') }}</label>
                            <input id="guard_name" name="guard_name" type="text" value="{{ old('guard_name', 'web') }}" class="fl-input w-full" readonly />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('roles.fields.guard_hint') }}</p>
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="fl-btn fl-btn-primary">
                            <x-tabler-plus-filled />
                            {{ __('roles.buttons.create') }}
                        </button>
                        <a class="fl-btn fl-btn-light" href="{{ route('roles.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
