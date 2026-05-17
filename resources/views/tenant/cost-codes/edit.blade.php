@extends('tenant.layouts.base')

@section('content')
<!-- Container -->
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cost_codes.edit_title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('cost_codes.edit_subtitle') }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('cost-codes.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('cost_codes.back') }}
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
                <h3 class="kt-card-title">{{ __('cost_codes.details_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <form method="POST" action="{{ route('cost-codes.update', $costCode) }}" class="grid gap-7">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="code">
                                {{ __('cost_codes.fields.code') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="code" name="code" type="text" value="{{ old('code', $costCode->code) }}"
                                   class="kt-input w-full" placeholder="e.g. CC-1001" required
                                   aria-invalid="@error('code') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('cost_codes.fields.code_hint') }}
                            </div>
                            @error('code')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="name">
                                {{ __('cost_codes.fields.name') }} <span class="text-destructive">*</span>
                            </label>
                            <input id="name" name="name" type="text" value="{{ old('name', $costCode->name) }}"
                                   class="kt-input w-full" placeholder="e.g. Office Supplies" required
                                   aria-invalid="@error('name') true @else false @enderror" />
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ __('cost_codes.fields.name_hint') }}
                            </div>
                            @error('name')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-1">
                            <label class="kt-form-label block mb-2" for="department_id">
                                {{ __('cost_codes.fields.department') }} <span class="text-destructive">*</span>
                            </label>
                            <select id="department_id" name="department_id" class="kt-select w-full" required
                                    aria-invalid="@error('department_id') true @else false @enderror">
                                <option value="">{{ __('cost_codes.fields.select_department') }}</option>
                                @foreach($departments as $department)
                                    <option value="{{ $department->id }}" {{ old('department_id', $costCode->department_id) == $department->id ? 'selected' : '' }}>
                                        {{ $department->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="pt-5 mt-2 flex justify-between items-center">
                        <div class="flex items-center gap-2.5">
                            <button type="submit" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-check"></i>
                                {{ __('cost_codes.buttons.update') }}
                            </button>
                            <a class="kt-btn kt-btn-light" href="{{ route('cost-codes.index') }}">{{ __('common.cancel') }}</a>
                        </div>
                        @can(App\Enums\Tenant\PermissionKey::DeleteCostCode->value)
                            <button type="button" class="kt-btn kt-btn-danger"
                                    onclick="if(confirm('{{ __('cost_codes.confirm_delete') }}')) { document.getElementById('delete-cost-code-form').submit(); }">
                                <i class="ki-filled ki-trash"></i>
                                {{ __('cost_codes.buttons.delete') }}
                            </button>
                        @endcan
                    </div>
                </form>

                @can(App\Enums\Tenant\PermissionKey::DeleteCostCode->value)
                    <form id="delete-cost-code-form" action="{{ route('cost-codes.destroy', $costCode) }}" method="POST" class="hidden">
                        @csrf @method('DELETE')
                    </form>
                @endcan
            </div>
        </div>
    </div>
</div>
<!-- End of Container -->
@endsection
