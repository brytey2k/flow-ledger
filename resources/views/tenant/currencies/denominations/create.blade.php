@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cash_count.denominations.add') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $currency->name }} ({{ $currency->symbol }})
            </div>
        </div>
        <a href="{{ route('currency.denominations.index', $currency) }}" class="kt-btn kt-btn-light">
            <i class="ki-filled ki-arrow-left"></i>
            {{ __('common.back') }}
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5 max-w-xl">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('cash_count.denominations.add') }}</h3>
            </div>
            <div class="kt-card-content p-5 lg:p-7.5">
                <form method="POST" action="{{ route('currency.denominations.store', $currency) }}" class="flex flex-col gap-5">
                    @csrf

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="label">
                            {{ __('cash_count.denominations.labels.label') }} <span class="text-danger">*</span>
                        </label>
                        <input type="text" id="label" name="label" value="{{ old('label') }}"
                               class="kt-input @error('label') border-danger @enderror"
                               placeholder="e.g. GHS 50, 1p coin">
                        @error('label')
                            <p class="text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="value">
                            {{ __('cash_count.denominations.labels.value') }} ({{ $currency->symbol }}) <span class="text-danger">*</span>
                        </label>
                        <input type="number" id="value" name="value" value="{{ old('value') }}"
                               step="0.0001" min="0.001"
                               class="kt-input @error('value') border-danger @enderror"
                               placeholder="e.g. 50.0000">
                        @error('value')
                            <p class="text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <label class="text-sm font-medium text-foreground" for="type">
                            {{ __('cash_count.denominations.labels.type') }} <span class="text-danger">*</span>
                        </label>
                        <select id="type" name="type" class="kt-select @error('type') border-danger @enderror">
                            @foreach(\App\Enums\Tenant\CurrencyDenominationType::cases() as $denominationType)
                                <option value="{{ $denominationType->value }}" {{ old('type', 'note') === $denominationType->value ? 'selected' : '' }}>
                                    {{ __('cash_count.denominations.types.' . $denominationType->value) }}
                                </option>
                            @endforeach
                        </select>
                        @error('type')
                            <p class="text-sm text-danger">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-2.5 pt-2">
                        <button type="submit" class="kt-btn kt-btn-primary">{{ __('common.save') }}</button>
                        <a href="{{ route('currency.denominations.index', $currency) }}" class="kt-btn kt-btn-light">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
