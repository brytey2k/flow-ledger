@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('currencies.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('currencies.create_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('currencies.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('currencies.back') }}
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
                <h3 class="kt-card-title">{{ __('currencies.details_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('currencies.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 gap-5">
                        <!-- Currency Name -->
                        <div>
                            <label class="kt-form-label block mb-2" for="name">
                                {{ __('currencies.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}" class="kt-input w-full" placeholder="e.g. Ghana Cedi, US Dollar" required aria-invalid="@error('name') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('currencies.fields.name_hint') }}</p>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Currency Code -->
                        <div>
                            <label class="kt-form-label block mb-2" for="short_name">
                                {{ __('currencies.fields.code') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="short_name" name="short_name" type="text" value="{{ old('short_name') }}" class="kt-input w-full" placeholder="e.g. GHS, USD" maxlength="10" required aria-invalid="@error('short_name') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('currencies.fields.code_hint') }}</p>
                            @error('short_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Currency Symbol -->
                        <div>
                            <label class="kt-form-label block mb-2" for="symbol">
                                {{ __('currencies.fields.symbol') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="symbol" name="symbol" type="text" value="{{ old('symbol') }}" class="kt-input w-full" placeholder="e.g. ₵, $" maxlength="10" required aria-invalid="@error('symbol') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('currencies.fields.symbol_hint') }}</p>
                            @error('symbol')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            {{ __('currencies.buttons.create') }}
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('currencies.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
