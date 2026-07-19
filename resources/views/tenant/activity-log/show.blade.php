@extends('tenant.layouts.base')

@section('content')
@php $activityLogRepository = app(\App\Repositories\ActivityLogRepository::class); @endphp

<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center lg:items-end justify-between gap-5 pb-7.5">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('activity_log.show.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $log->description }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            <a class="kt-btn kt-btn-outline" href="{{ route('activity-log.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('common.back') }}
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="kt-card">
        <div class="kt-card-header">
            <h3 class="kt-card-title">{{ __('activity_log.show.details_card') }}</h3>
        </div>
        <div class="kt-card-content grid gap-4 p-5 lg:p-7.5 lg:grid-cols-2">
            <div>
                <p class="text-sm text-muted-foreground">{{ __('common.event') }}</p>
                <p class="text-sm font-mono text-foreground">{{ $log->event ?? '—' }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('common.when') }}</p>
                <p class="text-sm text-foreground">{{ $log->created_at->format('M d, Y g:i A') }}</p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('common.subject') }}</p>
                <p class="text-sm text-foreground">
                    <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $activityLogRepository->subjectLabel($log->subject_type) }}</span>
                    @if($log->subject)
                        #{{ $log->subject_id }}
                    @else
                        <span class="italic text-muted-foreground">{{ __('common.deleted') }}</span>
                    @endif
                </p>
            </div>

            <div>
                <p class="text-sm text-muted-foreground">{{ __('common.performed_by') }}</p>
                <p class="text-sm text-foreground">
                    @if($log->causer)
                        {{ $log->causer->name }} <span class="text-secondary-foreground">({{ $log->causer->email }})</span>
                    @else
                        <span class="italic text-muted-foreground">{{ __('common.system') }}</span>
                    @endif
                </p>
            </div>

            <div class="lg:col-span-2">
                <p class="text-sm text-muted-foreground">{{ __('common.columns.description') }}</p>
                <p class="text-sm text-foreground">{{ $log->description }}</p>
            </div>

            <div class="lg:col-span-2">
                <p class="text-sm text-muted-foreground">{{ __('activity_log.show.properties') }}</p>
                @if($log->properties->isEmpty())
                    <p class="text-sm italic text-muted-foreground">{{ __('activity_log.show.no_properties') }}</p>
                @else
                    <pre class="mt-1 overflow-x-auto rounded-lg bg-muted/30 p-4 text-xs">{{ json_encode($log->properties, JSON_PRETTY_PRINT) }}</pre>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
