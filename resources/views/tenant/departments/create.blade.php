@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('departments.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('departments.create_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="fl-btn fl-btn-outline" href="{{ route('departments.index') }}">
                <x-tabler-arrow-left />
                {{ __('departments.back') }}
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
                <h3 class="fl-card-title">{{ __('departments.details_card') }}</h3>
            </div>
            <div class="fl-card-content">
                <form method="POST" action="{{ route('departments.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1 lg:col-span-1">
                            <label class="fl-form-label block mb-2" for="name">
                                {{ __('departments.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}"
                                   class="fl-input w-full" placeholder="e.g. Finance" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('departments.fields.name_hint') }}
                            </div>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="fl-btn fl-btn-primary">
                            <x-tabler-plus-filled />
                            {{ __('departments.buttons.create') }}
                        </button>
                        <a class="fl-btn fl-btn-light" href="{{ route('departments.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
