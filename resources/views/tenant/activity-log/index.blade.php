@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('activity_log.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('activity_log.subtitle') }}
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Filters --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('activity_log.filters.heading') }}</h3>
            </div>
            <div class="kt-card-content p-5">
                <form method="GET" action="{{ route('activity-log.index') }}" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="kt-form-label block mb-1.5" for="subject_type">{{ __('activity_log.filters.subject_type') }}</label>
                        <select id="subject_type" name="subject_type" class="kt-select w-full">
                            <option value="">{{ __('activity_log.filters.all_types') }}</option>
                            <option value="user" {{ request('subject_type') === 'user' ? 'selected' : '' }}>{{ __('activity_log.filters.user') }}</option>
                            <option value="staff" {{ request('subject_type') === 'staff' ? 'selected' : '' }}>{{ __('activity_log.filters.staff') }}</option>
                            <option value="payment_request" {{ request('subject_type') === 'payment_request' ? 'selected' : '' }}>{{ __('activity_log.filters.payment_request') }}</option>
                            <option value="retirement_request" {{ request('subject_type') === 'retirement_request' ? 'selected' : '' }}>{{ __('activity_log.filters.retirement_request') }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="kt-form-label block mb-1.5" for="event">{{ __('activity_log.filters.event') }}</label>
                        <input id="event" name="event" type="text" class="kt-input w-full"
                               placeholder="{{ __('activity_log.filters.event_placeholder') }}" value="{{ request('event') }}">
                    </div>

                    <div>
                        <label class="kt-form-label block mb-1.5" for="causer">{{ __('activity_log.filters.performed_by') }}</label>
                        <input id="causer" name="causer" type="text" class="kt-input w-full"
                               placeholder="{{ __('activity_log.filters.performed_by_placeholder') }}" value="{{ request('causer') }}">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-magnifier"></i>
                            {{ __('common.filter') }}
                        </button>
                        @if(request()->hasAny(['subject_type', 'event', 'causer']))
                            <a href="{{ route('activity-log.index') }}" class="kt-btn kt-btn-light">{{ __('common.clear') }}</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Log Table --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    {{ __('activity_log.entries_heading') }}
                    <span class="text-sm font-normal text-secondary-foreground ml-2">({{ $logs->total() }} {{ __('activity_log.total') }})</span>
                </h3>
            </div>
            <div class="kt-card-content p-0">
                @if($logs->isEmpty())
                    <div class="p-10 text-center text-sm text-secondary-foreground">
                        {{ __('activity_log.no_entries') }}
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="kt-table w-full text-sm">
                            <thead>
                                <tr class="border-b border-border">
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">{{ __('common.when') }}</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">{{ __('common.subject') }}</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">{{ __('common.event') }}</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">{{ __('common.columns.description') }}</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">{{ __('common.performed_by') }}</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">{{ __('common.details') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                @foreach($logs as $log)
                                    @php
                                        $subjectName = class_basename($log->subject_type ?? '');
                                        $subjectLabel = match($subjectName) {
                                            'PaymentRequest'    => 'Payment Request',
                                            'RetirementRequest' => 'Retirement Request',
                                            default             => $subjectName,
                                        };
                                        $eventColor = match(true) {
                                            str_ends_with($log->event ?? '', '.created') => 'text-success',
                                            str_ends_with($log->event ?? '', '.deleted') || str_ends_with($log->event ?? '', '.cancelled') => 'text-destructive',
                                            str_ends_with($log->event ?? '', '.approved') => 'text-success',
                                            str_ends_with($log->event ?? '', '.rejected') || str_ends_with($log->event ?? '', '.sent_back') => 'text-warning',
                                            default => 'text-secondary-foreground',
                                        };
                                    @endphp
                                    <tr class="hover:bg-muted/30">
                                        <td class="px-5 py-3 whitespace-nowrap text-secondary-foreground">
                                            {{ $log->created_at->format('M d, Y') }}<br>
                                            <span class="text-xs">{{ $log->created_at->format('g:i A') }}</span>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $subjectLabel }}</span>
                                            @if($log->subject)
                                                <div class="text-xs text-secondary-foreground mt-0.5">
                                                    #{{ $log->subject_id }}
                                                </div>
                                            @else
                                                <div class="text-xs text-muted-foreground mt-0.5 italic">{{ __('common.deleted') }}</div>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="font-mono text-xs {{ $eventColor }}">
                                                {{ $log->event ?? '—' }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-3 text-mono">
                                            {{ $log->description }}
                                        </td>
                                        <td class="px-5 py-3">
                                            @if($log->causer)
                                                <span class="font-medium text-mono">{{ $log->causer->name }}</span>
                                                <div class="text-xs text-secondary-foreground">{{ $log->causer->email }}</div>
                                            @else
                                                <span class="text-secondary-foreground italic">{{ __('common.system') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            @php $props = $log->properties->except(['old_status', 'new_status', 'comment'])->toArray(); @endphp
                                            @if($log->getProperty('old_status') && $log->getProperty('new_status'))
                                                <span class="text-xs text-secondary-foreground">
                                                    {{ ucwords(str_replace('_', ' ', $log->getProperty('old_status'))) }}
                                                    → {{ ucwords(str_replace('_', ' ', $log->getProperty('new_status'))) }}
                                                </span>
                                            @endif
                                            @if($log->getProperty('comment'))
                                                <p class="text-xs italic text-secondary-foreground mt-0.5">"{{ Str::limit($log->getProperty('comment'), 80) }}"</p>
                                            @endif
                                            @foreach($props as $key => $value)
                                                <div class="text-xs text-secondary-foreground">
                                                    <span class="font-medium">{{ ucwords(str_replace('_', ' ', $key)) }}:</span>
                                                    {{ is_string($value) ? $value : json_encode($value) }}
                                                </div>
                                            @endforeach
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if($logs->hasPages())
                        <div class="px-5 py-4 border-t border-border">
                            {{ $logs->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
