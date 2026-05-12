@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('branches.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('branches.create_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('branches.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('branches.back') }}
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
                <h3 class="kt-card-title">{{ __('branches.details_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('branches.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1 lg:col-span-2">
                            <label class="kt-form-label block mb-2" for="name">
                                {{ __('branches.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}"
                                   class="kt-input w-full" placeholder="e.g. Accra Regional Office" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('branches.fields.name_hint') }}
                            </div>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="code">
                                {{ __('branches.fields.code') }}
                            </label>
                            <input id="code" name="code" type="text" value="{{ old('code') }}"
                                   class="kt-input w-full" placeholder="e.g. ACC-REG"
                                   aria-invalid="@error('code') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('branches.fields.code_hint') }}
                            </div>
                            @error('code')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="position">
                                {{ __('branches.fields.position') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="position" name="position" type="number" min="1"
                                   value="{{ old('position', 1) }}"
                                   class="kt-input w-full" placeholder="e.g. 1" required
                                   aria-invalid="@error('position') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('branches.fields.position_hint') }}
                            </div>
                            @error('position')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="level_id">
                                {{ __('branches.fields.level') }} <span class="text-destructive">*</span>
                            </label>
                            <select id="level_id" name="level_id" class="kt-input w-full" required
                                    aria-invalid="@error('level_id') true @else false @enderror">
                                <option value="">{{ __('branches.fields.select_level') }}</option>
                                @foreach($levels as $level)
                                    <option value="{{ $level->id }}" {{ old('level_id') == $level->id ? 'selected' : '' }}>
                                        {{ $level->name }} (Position: {{ $level->position }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('branches.fields.level_hint') }}
                            </div>
                            @error('level_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="currency_id">
                                {{ __('branches.fields.currency') }} <span class="text-destructive">*</span>
                            </label>
                            <select id="currency_id" name="currency_id" class="kt-input w-full" required
                                    aria-invalid="@error('currency_id') true @else false @enderror">
                                <option value="">{{ __('branches.fields.select_currency') }}</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}" {{ old('currency_id') == $currency->id ? 'selected' : '' }}>
                                        {{ $currency->name }} ({{ $currency->short_name }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('branches.fields.currency_hint') }}
                            </div>
                            @error('currency_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1 lg:col-span-1">
                            <label class="kt-form-label block mb-2" for="parent_id">
                                {{ __('branches.fields.parent') }}
                            </label>
                            <select id="parent_id" name="parent_id" class="kt-input w-full"
                                    aria-invalid="@error('parent_id') true @else false @enderror">
                                <option value="">{{ __('branches.fields.none_root') }}</option>
                                @foreach($branches as $b)
                                    @php $indent = str_repeat('—', $b->depth); @endphp
                                    <option value="{{ $b->id }}" {{ old('parent_id') == $b->id ? 'selected' : '' }}>
                                        @if($b->depth > 0){{ $indent }} @endif{{ $b->name }} ({{ $b->level->name }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('branches.fields.parent_hint') }}
                            </div>
                            @error('parent_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            {{ __('branches.buttons.create') }}
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('branches.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
