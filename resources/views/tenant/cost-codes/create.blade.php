@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cost_codes.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('cost_codes.create_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="fl-btn fl-btn-outline" href="{{ route('cost-codes.index') }}">
                <x-tabler-arrow-left />
                {{ __('cost_codes.back') }}
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
                <h3 class="fl-card-title">{{ __('cost_codes.details_card') }}</h3>
            </div>
            <div class="fl-card-content">
                <form method="POST" action="{{ route('cost-codes.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1">
                            <label class="fl-form-label block mb-2" for="code">
                                {{ __('cost_codes.fields.code') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="code" name="code" type="text" value="{{ old('code') }}"
                                   class="fl-input w-full" placeholder="e.g. CC-1001" required
                                   aria-invalid="@error('code') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('cost_codes.fields.code_hint') }}
                            </div>
                            @error('code')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="fl-form-label block mb-2" for="name">
                                {{ __('cost_codes.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}"
                                   class="fl-input w-full" placeholder="e.g. Office Supplies" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('cost_codes.fields.name_hint') }}
                            </div>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="fl-form-label block mb-2" for="department_id">
                                {{ __('cost_codes.fields.department') }} <span class="text-destructive">*</span>
                            </label>
                            <select id="department_id" name="department_id" class="fl-select w-full" required
                                    aria-invalid="@error('department_id') true @else false @enderror">
                                <option value="">{{ __('cost_codes.fields.select_department') }}</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="fl-btn fl-btn-primary">
                            <x-tabler-plus-filled />
                            {{ __('cost_codes.buttons.create') }}
                        </button>
                        <a class="fl-btn fl-btn-light" href="{{ route('cost-codes.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
