@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('settings.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('settings.subtitle') }}
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="grid gap-5 lg:gap-7.5">
        @csrf
        @method('PUT')

        {{-- Branding --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('settings.branding_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div>
                        <label class="kt-form-label block mb-2" for="logo">
                            {{ __('settings.fields.logo') }}
                        </label>

                        @if($logoUrl)
                            <div class="mb-3 flex items-center gap-4">
                                <img src="{{ $logoUrl }}" alt="{{ __('settings.fields.logo_preview_alt') }}"
                                     class="max-h-[44px] max-w-[200px] object-contain rounded border border-border p-2 bg-muted">
                                <div class="flex items-center gap-2">
                                    <input type="checkbox" id="remove_logo" name="remove_logo" value="1" class="form-checkbox">
                                    <label for="remove_logo" class="kt-form-label mb-0 text-sm text-destructive">
                                        {{ __('settings.fields.remove_logo') }}
                                    </label>
                                </div>
                            </div>
                        @endif

                        <input id="logo" name="logo" type="file" accept="image/png,image/jpeg,image/webp"
                               class="kt-input w-full"
                               aria-invalid="@error('logo') true @else false @enderror" />
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ __('settings.fields.logo_hint') }}
                        </div>
                        @error('logo')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Advance Defaults --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('settings.advance_defaults_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div>
                        <label class="kt-form-label block mb-2" for="default_advance_cost_code_id">
                            {{ __('settings.fields.default_advance_cost_code') }}
                        </label>
                        <select id="default_advance_cost_code_id" name="default_advance_cost_code_id"
                                class="kt-select w-full"
                                aria-invalid="@error('default_advance_cost_code_id') true @else false @enderror">
                            <option value="">{{ __('settings.fields.no_default_cost_code') }}</option>
                            @foreach($costCodes as $costCode)
                                <option value="{{ $costCode->id }}"
                                    {{ old('default_advance_cost_code_id', $defaultAdvanceCostCodeId) == $costCode->id ? 'selected' : '' }}>
                                    {{ $costCode->code }} — {{ $costCode->name }}
                                    @if($costCode->department)
                                        ({{ $costCode->department->name }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ __('settings.fields.default_advance_cost_code_hint') }}
                        </div>
                        @error('default_advance_cost_code_id')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Expense Settings --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('settings.expense_settings_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="flex items-start gap-3">
                        <input type="hidden" name="require_expense_source_documents" value="0">
                        <input type="checkbox" id="require_expense_source_documents"
                               name="require_expense_source_documents" value="1"
                               class="mt-1"
                               {{ old('require_expense_source_documents', $requireExpenseSourceDocuments) ? 'checked' : '' }}>
                        <div>
                            <label class="kt-form-label mb-0" for="require_expense_source_documents">
                                {{ __('settings.fields.require_expense_source_documents') }}
                            </label>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ __('settings.fields.require_expense_source_documents_hint') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Retirement Settings --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('settings.retirement_settings_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    <div class="flex items-start gap-3">
                        <input type="hidden" name="require_retirement_source_documents" value="0">
                        <input type="checkbox" id="require_retirement_source_documents"
                               name="require_retirement_source_documents" value="1"
                               class="mt-1"
                               {{ old('require_retirement_source_documents', $requireRetirementSourceDocuments) ? 'checked' : '' }}>
                        <div>
                            <label class="kt-form-label mb-0" for="require_retirement_source_documents">
                                {{ __('settings.fields.require_retirement_source_documents') }}
                            </label>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ __('settings.fields.require_retirement_source_documents_hint') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Retirement Reminders --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('settings.retirement_reminders_card') }}</h3>
            </div>
            <div class="kt-card-content">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                    {{-- Grace period --}}
                    <div>
                        <label class="kt-form-label block mb-2" for="retirement_reminder_grace_period_days">
                            {{ __('settings.fields.retirement_reminder_grace_period_days') }}
                        </label>
                        <input type="number" id="retirement_reminder_grace_period_days"
                               name="retirement_reminder_grace_period_days" min="1"
                               value="{{ old('retirement_reminder_grace_period_days', $retirementReminderSettings['grace_period_days']) }}"
                               class="kt-input w-full"
                               aria-invalid="@error('retirement_reminder_grace_period_days') true @else false @enderror">
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ __('settings.fields.retirement_reminder_grace_period_days_hint') }}
                        </div>
                        @error('retirement_reminder_grace_period_days')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Frequency --}}
                    <div>
                        <label class="kt-form-label block mb-2" for="retirement_reminder_frequency_days">
                            {{ __('settings.fields.retirement_reminder_frequency_days') }}
                        </label>
                        <input type="number" id="retirement_reminder_frequency_days"
                               name="retirement_reminder_frequency_days" min="1"
                               value="{{ old('retirement_reminder_frequency_days', $retirementReminderSettings['frequency_days']) }}"
                               class="kt-input w-full"
                               aria-invalid="@error('retirement_reminder_frequency_days') true @else false @enderror">
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ __('settings.fields.retirement_reminder_frequency_days_hint') }}
                        </div>
                        @error('retirement_reminder_frequency_days')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Notify submitter --}}
                    <div class="flex items-start gap-3">
                        <input type="hidden" name="retirement_reminder_notify_submitter" value="0">
                        <input type="checkbox" id="retirement_reminder_notify_submitter"
                               name="retirement_reminder_notify_submitter" value="1"
                               class="mt-1"
                               {{ old('retirement_reminder_notify_submitter', $retirementReminderSettings['notify_submitter']) ? 'checked' : '' }}>
                        <div>
                            <label class="kt-form-label mb-0" for="retirement_reminder_notify_submitter">
                                {{ __('settings.fields.retirement_reminder_notify_submitter') }}
                            </label>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ __('settings.fields.retirement_reminder_notify_submitter_hint') }}
                            </p>
                        </div>
                    </div>

                    {{-- Notify approvers --}}
                    <div class="flex items-start gap-3">
                        <input type="hidden" name="retirement_reminder_notify_approvers" value="0">
                        <input type="checkbox" id="retirement_reminder_notify_approvers"
                               name="retirement_reminder_notify_approvers" value="1"
                               class="mt-1"
                               {{ old('retirement_reminder_notify_approvers', $retirementReminderSettings['notify_approvers']) ? 'checked' : '' }}>
                        <div>
                            <label class="kt-form-label mb-0" for="retirement_reminder_notify_approvers">
                                {{ __('settings.fields.retirement_reminder_notify_approvers') }}
                            </label>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ __('settings.fields.retirement_reminder_notify_approvers_hint') }}
                            </p>
                        </div>
                    </div>

                    {{-- Notify roles --}}
                    <div class="lg:col-span-2">
                        <label class="kt-form-label block mb-2">
                            {{ __('settings.fields.retirement_reminder_notify_role_ids') }}
                        </label>
                        <div class="flex flex-wrap gap-4">
                            @foreach($roles as $role)
                                <div class="flex items-center gap-2">
                                    <input type="checkbox"
                                           id="retirement_reminder_role_{{ $role->id }}"
                                           name="retirement_reminder_notify_role_ids[]"
                                           value="{{ $role->id }}"
                                           {{ in_array($role->id, old('retirement_reminder_notify_role_ids', $retirementReminderSettings['notify_role_ids'])) ? 'checked' : '' }}>
                                    <label for="retirement_reminder_role_{{ $role->id }}" class="kt-form-label mb-0 text-sm">
                                        {{ $role->name }}
                                    </label>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-1 text-xs text-muted-foreground">
                            {{ __('settings.fields.retirement_reminder_notify_role_ids_hint') }}
                        </div>
                        @error('retirement_reminder_notify_role_ids')
                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-start items-center gap-2.5">
            <button type="submit" class="kt-btn kt-btn-primary">
                <i class="ki-filled ki-check"></i>
                {{ __('common.save') }}
            </button>
        </div>
    </form>
</div>
@endsection
