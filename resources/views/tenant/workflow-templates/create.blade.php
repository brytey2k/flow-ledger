@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.create_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('workflows.create_subtitle') }}
            </div>
        </div>
        <a class="kt-btn kt-btn-outline" href="{{ route('workflow-templates.index') }}">
            <i class="ki-filled ki-arrow-left"></i>
            {{ __('workflows.back') }}
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('workflows.details_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('workflow-templates.store') }}" class="grid gap-7">
                    @csrf

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div>
                            <label class="kt-form-label block mb-2" for="name">
                                {{ __('workflows.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name') }}"
                                   class="kt-input w-full" placeholder="e.g. Standard Advance Approval"
                                   aria-invalid="@error('name') true @else false @enderror" />
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="kt-form-label block mb-2" for="type">
                                {{ __('workflows.fields.type') }} <span class="text-destructive">*</span>
                            </label>
                            <select id="type" name="type" class="kt-select w-full"
                                    aria-invalid="@error('type') true @else false @enderror">
                                <option value="">{{ __('workflows.fields.select_type') }}</option>
                                <option value="advance" {{ old('type') === 'advance' ? 'selected' : '' }}>{{ __('workflows.fields.type_advance') }}</option>
                                <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>{{ __('workflows.fields.type_expense') }}</option>
                                <option value="retirement" {{ old('type') === 'retirement' ? 'selected' : '' }}>{{ __('workflows.fields.type_retirement') }}</option>
                            </select>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('workflows.fields.type_hint') }}
                            </div>
                            @error('type')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="kt-form-label block mb-2" for="branch_id">
                                {{ __('workflows.fields.branch') }}
                            </label>
                            <select id="branch_id" name="branch_id" class="kt-select w-full"
                                    aria-invalid="@error('branch_id') true @else false @enderror">
                                <option value="">{{ __('workflows.fields.branch_master') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                        {{ $branch->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('workflows.fields.branch_hint') }}
                            </div>
                            @error('branch_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-start items-center gap-2.5">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-plus"></i>
                            {{ __('workflows.buttons.create') }}
                        </button>
                        <a class="kt-btn kt-btn-light" href="{{ route('workflow-templates.index') }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
