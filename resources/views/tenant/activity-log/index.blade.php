@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Activity Log</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Audit trail of all actions performed in the system
            </div>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">

        {{-- Filters --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">Filters</h3>
            </div>
            <div class="kt-card-content p-5">
                <form method="GET" action="{{ route('activity-log.index') }}" class="grid grid-cols-1 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="kt-form-label block mb-1.5" for="subject_type">Subject Type</label>
                        <select id="subject_type" name="subject_type" class="kt-select w-full">
                            <option value="">All types</option>
                            <option value="user" {{ request('subject_type') === 'user' ? 'selected' : '' }}>User</option>
                            <option value="staff" {{ request('subject_type') === 'staff' ? 'selected' : '' }}>Staff</option>
                            <option value="payment_request" {{ request('subject_type') === 'payment_request' ? 'selected' : '' }}>Payment Request</option>
                            <option value="retirement_request" {{ request('subject_type') === 'retirement_request' ? 'selected' : '' }}>Retirement Request</option>
                        </select>
                    </div>

                    <div>
                        <label class="kt-form-label block mb-1.5" for="event">Event</label>
                        <input id="event" name="event" type="text" class="kt-input w-full"
                               placeholder="e.g. user.created" value="{{ request('event') }}">
                    </div>

                    <div>
                        <label class="kt-form-label block mb-1.5" for="causer">Performed By</label>
                        <input id="causer" name="causer" type="text" class="kt-input w-full"
                               placeholder="Name or email…" value="{{ request('causer') }}">
                    </div>

                    <div class="flex items-end gap-2">
                        <button type="submit" class="kt-btn kt-btn-primary">
                            <i class="ki-filled ki-magnifier"></i>
                            Filter
                        </button>
                        @if(request()->hasAny(['subject_type', 'event', 'causer']))
                            <a href="{{ route('activity-log.index') }}" class="kt-btn kt-btn-light">Clear</a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        {{-- Log Table --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">
                    Entries
                    <span class="text-sm font-normal text-secondary-foreground ml-2">({{ $logs->total() }} total)</span>
                </h3>
            </div>
            <div class="kt-card-content p-0">
                @if($logs->isEmpty())
                    <div class="p-10 text-center text-sm text-secondary-foreground">
                        No activity log entries found.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="kt-table w-full text-sm">
                            <thead>
                                <tr class="border-b border-border">
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">When</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">Subject</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">Event</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">Description</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">Performed By</th>
                                    <th class="text-left px-5 py-3 font-medium text-secondary-foreground">Details</th>
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
                                            <span class="text-xs">{{ $log->created_at->format('H:i') }}</span>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $subjectLabel }}</span>
                                            @if($log->subject)
                                                <div class="text-xs text-secondary-foreground mt-0.5">
                                                    #{{ $log->subject_id }}
                                                </div>
                                            @else
                                                <div class="text-xs text-muted-foreground mt-0.5 italic">deleted</div>
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
                                                <span class="text-secondary-foreground italic">System</span>
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
