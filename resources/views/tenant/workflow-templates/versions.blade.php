@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.versions.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $workflowTemplate->name }} &bull; {{ __('workflows.versions.subtitle') }}
            </div>
        </div>
        <a class="kt-btn kt-btn-outline" href="{{ route('workflow-templates.show', $workflowTemplate) }}">
            <i class="ki-filled ki-arrow-left"></i>
            {{ __('workflows.versions.back') }}
        </a>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="kt-card kt-card-grid">
            <div class="kt-card-table">
                <div class="kt-scrollable-x-auto border-b border-border">
                    <table class="kt-table kt-table-border">
                        <thead>
                            <tr>
                                <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.versions.columns.version') }}</span></span></th>
                                <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.versions.columns.created_at') }}</span></span></th>
                                <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.versions.columns.status') }}</span></span></th>
                                <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.versions.columns.total_requests') }}</span></span></th>
                                <th class="min-w-[140px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.versions.columns.active_requests') }}</span></span></th>
                                <th class="min-w-[100px] text-center"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versions as $version)
                                <tr>
                                    <td><span class="kt-badge kt-badge-sm kt-badge-primary">{{ $version->version }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $version->created_at->format('M d, Y H:i') }}</span></td>
                                    <td>
                                        @if($version->isDraft())
                                            <span class="kt-badge kt-badge-sm kt-badge-warning">{{ __('workflows.versions.draft') }}</span>
                                        @elseif($version->is_current)
                                            <span class="kt-badge kt-badge-sm kt-badge-success">{{ __('workflows.versions.current') }}</span>
                                        @else
                                            <span class="kt-badge kt-badge-sm kt-badge-outline">{{ __('workflows.versions.superseded') }}</span>
                                        @endif
                                    </td>
                                    <td><span class="text-sm text-foreground">{{ $version->instances_count }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $version->active_instances_count }}</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('workflow-templates.show', $version) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                                            <i class="ki-filled ki-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
