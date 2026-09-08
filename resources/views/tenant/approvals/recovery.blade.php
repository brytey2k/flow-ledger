@extends('tenant.layouts.base')

@php
    $subject = $instanceStage->instance->workflowable;
    $backRoute = $subject instanceof \App\Models\Tenant\RetirementRequest
        ? route('retirement-requests.show', $subject)
        : route('payment-requests.show', $subject);
@endphp

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.separation.recovery_title') }}</h1>
            <p class="text-sm text-secondary-foreground">
                {{ $instanceStage->instance->template->name }} &rsaquo; {{ $instanceStage->stage->name }}
            </p>
        </div>
        <a class="sgh-btn sgh-btn-outline" href="{{ $backRoute }}">
            <x-tabler-arrow-left />
            {{ __('common.back') }}
        </a>
    </div>

    <div class="grid max-w-3xl gap-5">
        <div class="sgh-alert sgh-alert-light sgh-alert-warning">
            <span class="sgh-alert-icon"><x-tabler-alert-triangle class="text-xl" /></span>
            <div class="sgh-alert-content">
                <div class="font-medium text-mono">{{ __('workflows.separation.recovery_scope_heading') }}</div>
                <div class="sgh-alert-description">{{ __('workflows.separation.recovery_scope_body') }}</div>
            </div>
        </div>

        <div class="sgh-card">
            <div class="sgh-card-header">
                <h2 class="sgh-card-title">{{ __('workflows.separation.recovery_form_heading') }}</h2>
            </div>
            <div class="sgh-card-content p-5 lg:p-7.5">
                <form method="POST" action="{{ route('approvals.recovery.store', $instanceStage) }}" class="grid gap-5">
                    @csrf

                    <div>
                        <label for="role_id" class="sgh-form-label mb-2 block">
                            {{ __('workflows.separation.recovery_role') }} <span class="text-destructive">*</span>
                        </label>
                        <select id="role_id" name="role_id" class="sgh-select w-full" required>
                            <option value="">{{ __('workflows.separation.select_recovery_role') }}</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->id }}" @selected((string) old('role_id') === (string) $role->id)>
                                    {{ $role->name }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-muted-foreground">{{ __('workflows.separation.recovery_role_hint') }}</p>
                        @error('role_id') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="reason" class="sgh-form-label mb-2 block">
                            {{ __('workflows.separation.recovery_reason') }} <span class="text-destructive">*</span>
                        </label>
                        <textarea id="reason" name="reason" rows="4" maxlength="1000" class="sgh-textarea w-full" required>{{ old('reason') }}</textarea>
                        <p class="mt-1 text-xs text-muted-foreground">{{ __('workflows.separation.recovery_reason_hint') }}</p>
                        @error('reason') <p class="mt-1 text-sm text-destructive">{{ $message }}</p> @enderror
                    </div>

                    <div class="flex flex-wrap gap-2.5">
                        <button type="submit" class="sgh-btn sgh-btn-warning">
                            <x-tabler-check-filled />
                            {{ __('workflows.separation.apply_recovery_and_retry') }}
                        </button>
                        <a class="sgh-btn sgh-btn-light" href="{{ $backRoute }}">{{ __('common.cancel') }}</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
