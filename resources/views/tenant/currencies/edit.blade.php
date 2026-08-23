@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('currencies.edit_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('currencies.edit_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="sgh-btn sgh-btn-outline" href="{{ route('currencies.index') }}">
                <x-tabler-arrow-left />
                {{ __('currencies.back') }}
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
                <h3 class="sgh-card-title">{{ __('currencies.details_card') }}</h3>
            </div>
            <div class="sgh-card-content">
                <form method="POST" action="{{ route('currencies.update', $currency) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-5">
                        <!-- Currency Name -->
                        <div>
                            <label class="sgh-form-label block mb-2" for="name">
                                {{ __('currencies.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $currency->name) }}" class="sgh-input w-full" placeholder="e.g. Ghana Cedi, US Dollar" required aria-invalid="@error('name') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('currencies.fields.name_hint') }}</p>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Currency Code -->
                        <div>
                            <label class="sgh-form-label block mb-2" for="short_name">
                                {{ __('currencies.fields.code') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="short_name" name="short_name" type="text" value="{{ old('short_name', $currency->short_name) }}" class="sgh-input w-full" placeholder="e.g. GHS, USD" maxlength="10" required aria-invalid="@error('short_name') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('currencies.fields.code_hint') }}</p>
                            @error('short_name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Currency Symbol -->
                        <div>
                            <label class="sgh-form-label block mb-2" for="symbol">
                                {{ __('currencies.fields.symbol') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="symbol" name="symbol" type="text" value="{{ old('symbol', $currency->symbol) }}" class="sgh-input w-full" placeholder="e.g. ₵, $" maxlength="10" required aria-invalid="@error('symbol') true @else false @enderror" />
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('currencies.fields.symbol_hint') }}</p>
                            @error('symbol')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="sgh-btn sgh-btn-primary">
                            <x-tabler-check-filled />
                            {{ __('currencies.buttons.update') }}
                        </button>
                        <a class="sgh-btn sgh-btn-light" href="{{ route('currencies.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Danger Zone -->
        @can(\App\Enums\Tenant\PermissionKey::DeleteCurrency->value)
            <div class="sgh-card">
                <div class="sgh-card-header">
                    <h3 class="sgh-card-title text-destructive">{{ __('currencies.danger_zone') }}</h3>
                </div>
                <div class="sgh-card-content">
                    <div class="flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-medium text-foreground mb-1">Delete Currency</h4>
                            <p class="text-sm text-secondary-foreground">{{ __('currencies.delete_warning') }}</p>
                        </div>
                        <form action="{{ route('currencies.destroy', $currency) }}" method="POST" onsubmit="return confirm('{{ __('currencies.confirm_delete') }}');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="sgh-btn sgh-btn-danger">
                                <x-tabler-trash-filled />
                                {{ __('currencies.buttons.delete') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        @endcan
    </div>
</div>
<!-- End of Container -->
@endsection
