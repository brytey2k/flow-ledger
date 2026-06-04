@extends('tenant.layouts.base')

@section('content')
@php
    $thresholdsByBranch = $thresholds->keyBy('branch_id');
    $configuredThresholdCount = $thresholdsByBranch->count();
    $activeThresholdCount = $thresholdsByBranch->where('is_active', true)->count();
    $branchesWithCashbookCount = $branches->filter(static fn ($branch): bool => (bool) $branch->cashbook)->count();
    $pendingThresholdCount = max($branches->count() - $configuredThresholdCount, 0);
@endphp

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('cash_balance.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('cash_balance.subtitle') }}
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-alert kt-alert-light kt-alert-warning">
            <span class="kt-alert-icon"><i class="ki-filled ki-information-4 text-xl"></i></span>
            <div class="kt-alert-content">
                <h4 class="kt-alert-title">{{ __('cash_balance.configure_thresholds') }}</h4>
                <div class="kt-alert-description">{{ __('cash_balance.alert_description') }}</div>
            </div>
        </div>

        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            <div class="kt-card">
                <div class="kt-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('cash_balance.summary_configured_branches') }}</span>
                        <i class="ki-filled ki-shield-tick text-lg text-primary"></i>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold text-mono">{{ $configuredThresholdCount }}</span>
                        <span class="text-sm text-muted-foreground">{{ $branches->count() }} {{ __('cash_balance.summary_total_branches') }}</span>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('cash_balance.summary_active_alerts') }}</span>
                        <i class="ki-filled ki-notification-bing text-lg text-success"></i>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold text-mono">{{ $activeThresholdCount }}</span>
                        <span class="text-sm text-muted-foreground">{{ __('cash_balance.summary_notification_rules_enabled') }}</span>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('cash_balance.summary_cashbooks_covered') }}</span>
                        <i class="ki-filled ki-wallet text-lg text-warning"></i>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold text-mono">{{ $branchesWithCashbookCount }}</span>
                        <span class="text-sm text-muted-foreground">{{ __('cash_balance.summary_branches_ready') }}</span>
                    </div>
                </div>
            </div>

            <div class="kt-card">
                <div class="kt-card-content flex flex-col gap-2 p-5">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium text-muted-foreground">{{ __('cash_balance.summary_pending_setup') }}</span>
                        <i class="ki-filled ki-information-2 text-lg text-info"></i>
                    </div>
                    <div class="flex flex-col gap-1">
                        <span class="text-3xl font-semibold text-mono">{{ $pendingThresholdCount }}</span>
                        <span class="text-sm text-muted-foreground">{{ __('cash_balance.summary_branches_missing_threshold') }}</span>
                    </div>
                </div>
            </div>
        </div>

        @if($branches->isEmpty())
            <div class="kt-card">
                <div class="kt-card-content flex flex-col items-center justify-center gap-4 py-16">
                    <div class="flex size-16 items-center justify-center rounded-full bg-primary/10">
                        <i class="ki-filled ki-wallet text-3xl text-primary"></i>
                    </div>
                    <div class="flex flex-col items-center gap-2 text-center">
                        <h3 class="text-lg font-semibold text-mono">{{ __('cash_balance.empty.heading') }}</h3>
                        <p class="max-w-md text-sm text-muted-foreground">{{ __('cash_balance.empty.subtext') }}</p>
                    </div>
                </div>
            </div>
        @else
            <div class="grid gap-5 xl:grid-cols-2 xl:gap-7.5">
                @foreach($branches as $branch)
                    @php
                        $threshold = $thresholdsByBranch->get($branch->id);
                        $formId = 'threshold-form-' . $branch->id;
                        $isEditing = ! is_null($threshold);
                        $submittedBranchId = (int) old('branch_id');
                        $showErrors = $submittedBranchId === $branch->id;
                        $selectedRecipientIds = array_map(
                            'intval',
                            (array) old('notification_user_ids', $threshold?->notification_user_ids ?? []),
                        );
                    @endphp

                    <div class="kt-card kt-card-grid h-full">
                        <div class="kt-card-header items-start gap-4">
                            <div class="flex flex-col gap-2">
                                <div class="flex flex-wrap items-center gap-2.5">
                                    <h3 class="kt-card-title">{{ $branch->name }}</h3>
                                    <span class="kt-badge kt-badge-sm {{ is_null($threshold) ? 'kt-badge-outline' : 'kt-badge-primary kt-badge-outline' }}">
                                        {{ is_null($threshold) ? __('cash_balance.not_configured') : __('cash_balance.configured') }}
                                    </span>
                                    @if(! is_null($threshold))
                                        <span class="kt-badge kt-badge-sm {{ $threshold->is_active ? 'kt-badge-success kt-badge-outline' : 'kt-badge-warning kt-badge-outline' }}">
                                            {{ $threshold->is_active ? __('cash_balance.notifications_active') : __('cash_balance.notifications_disabled') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="text-sm text-secondary-foreground">
                                    @if($branch->cashbook)
                                        {{ __('cash_balance.current_balance') }}:
                                        <span class="font-medium text-mono">
                                            {{ $branch->currency?->symbol ?? '' }} {{ number_format((float) $branch->cashbook->balance, 2) }}
                                        </span>
                                    @else
                                        {{ __('cash_balance.no_cashbook') }}
                                    @endif
                                </div>
                            </div>

                            @if($branch->cashbook)
                                <div class="flex items-center gap-2">
                                    <span class="kt-badge kt-badge-sm kt-badge-outline">
                                        {{ $branch->currency?->symbol ?? '' }} {{ number_format((float) $branch->cashbook->balance, 2) }}
                                    </span>
                                </div>
                            @endif
                        </div>

                        <div class="kt-card-content flex flex-col gap-6 p-5 lg:p-7.5">
                            @if(! $branch->cashbook)
                                <div class="kt-alert kt-alert-light kt-alert-warning">
                                    <span class="kt-alert-icon"><i class="ki-filled ki-information-4 text-xl"></i></span>
                                    <div class="kt-alert-content">
                                        <h4 class="kt-alert-title">{{ __('cash_balance.cashbook_missing_title') }}</h4>
                                        <div class="kt-alert-description">{{ __('cash_balance.cashbook_missing_description') }}</div>
                                    </div>
                                </div>
                            @endif

                            <form id="{{ $formId }}" action="{{ is_null($threshold) ? route('cash-balance-thresholds.store') : route('cash-balance-thresholds.update', $threshold) }}" method="POST" class="grid gap-6">
                                @csrf
                                @if(! is_null($threshold))
                                    @method('PUT')
                                @endif

                                <input type="hidden" name="branch_id" value="{{ $branch->id }}">

                                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                                    <div>
                                        <label for="threshold-{{ $branch->id }}" class="kt-form-label block mb-2">
                                            {{ __('cash_balance.threshold_amount') }} <span class="text-destructive">*</span>
                                        </label>
                                        <div class="flex items-center gap-2">
                                            <span class="kt-badge kt-badge-sm kt-badge-outline shrink-0">{{ $branch->currency?->symbol ?? '' }}</span>
                                            <input
                                                type="number"
                                                id="threshold-{{ $branch->id }}"
                                                name="threshold_amount"
                                                step="0.01"
                                                min="0"
                                                max="999999.99"
                                                value="{{ old('threshold_amount', is_null($threshold) ? '' : $threshold->threshold_amount) }}"
                                                class="kt-input w-full"
                                                placeholder="0.00"
                                                required
                                                aria-invalid="@error('threshold_amount') true @else false @enderror"
                                            >
                                        </div>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ __('cash_balance.threshold_help') }}</p>
                                        @error('threshold_amount')
                                            @if($showErrors)
                                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                                            @endif
                                        @enderror
                                    </div>

                                    <div>
                                        <label for="cooldown-{{ $branch->id }}" class="kt-form-label block mb-2">
                                            {{ __('cash_balance.cooldown_minutes') }} <span class="text-destructive">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            id="cooldown-{{ $branch->id }}"
                                            name="cooldown_minutes"
                                            min="0"
                                            max="10080"
                                            value="{{ old('cooldown_minutes', is_null($threshold) ? '1440' : $threshold->cooldown_minutes) }}"
                                            class="kt-input w-full"
                                            placeholder="1440"
                                            required
                                            aria-invalid="@error('cooldown_minutes') true @else false @enderror"
                                        >
                                        <p class="mt-1 text-xs text-muted-foreground">{{ __('cash_balance.cooldown_help') }}</p>
                                        @error('cooldown_minutes')
                                            @if($showErrors)
                                                <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                                            @endif
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="recipients-{{ $branch->id }}" class="kt-form-label block mb-2">
                                        {{ __('cash_balance.notification_recipients') }}
                                    </label>
                                    <select
                                        id="recipients-{{ $branch->id }}"
                                        name="notification_user_ids[]"
                                        multiple
                                        class="kt-input w-full min-h-[140px]"
                                        aria-invalid="@error('notification_user_ids') true @else false @enderror"
                                    >
                                        @foreach($users as $user)
                                            <option
                                                value="{{ $user->id }}"
                                                @selected(in_array($user->id, $selectedRecipientIds, true))
                                            >
                                                {{ $user->first_name }} {{ $user->last_name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ __('cash_balance.recipients_help') }}</p>
                                    @error('notification_user_ids')
                                        @if($showErrors)
                                            <p class="mt-1 text-sm text-destructive">{{ $message }}</p>
                                        @endif
                                    @enderror
                                </div>

                                <div class="flex items-center gap-3">
                                    <input
                                        type="checkbox"
                                        id="active-{{ $branch->id }}"
                                        name="is_active"
                                        value="1"
                                        class="form-checkbox"
                                        @checked((bool) old('is_active', is_null($threshold) ? true : $threshold->is_active))
                                    >
                                    <label for="active-{{ $branch->id }}" class="kt-form-label mb-0">
                                        {{ __('cash_balance.enable_notifications') }}
                                    </label>
                                </div>
                            </form>

                            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-border pt-5">
                                <p class="text-2sm text-muted-foreground">
                                    {{ $isEditing ? __('cash_balance.rule_hint_update') : __('cash_balance.rule_hint_create') }}
                                </p>

                                <div class="flex items-center gap-2.5">
                                    @if(! is_null($threshold))
                                        <form action="{{ route('cash-balance-thresholds.destroy', $threshold) }}" method="POST" onsubmit="return confirm('{{ __('cash_balance.confirm_delete') }}');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="kt-btn kt-btn-light text-danger">
                                                <i class="ki-filled ki-trash"></i>
                                                {{ __('common.delete') }}
                                            </button>
                                        </form>
                                    @endif

                                    <button type="submit" form="{{ $formId }}" class="kt-btn kt-btn-primary">
                                        <i class="ki-filled ki-check"></i>
                                        {{ is_null($threshold) ? __('cash_balance.configure') : __('cash_balance.update') }}
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
