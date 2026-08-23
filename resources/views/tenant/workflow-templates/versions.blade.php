@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.versions.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ $workflowTemplate->name }} &bull; {{ __('workflows.versions.subtitle') }}
            </div>
        </div>
        <a class="fl-btn fl-btn-outline" href="{{ route('workflow-templates.show', $workflowTemplate) }}">
            <x-tabler-arrow-left />
            {{ __('workflows.versions.back') }}
        </a>
    </div>
</div>

<div class="fl-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="fl-card fl-card-grid">
            <div class="fl-card-table">
                <div class="fl-scrollable-x-auto border-b border-border">
                    <table class="fl-table fl-table-border">
                        <thead>
                            <tr>
                                <th class="min-w-[100px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('workflows.versions.columns.version') }}</span></span></th>
                                <th class="min-w-[160px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('workflows.versions.columns.created_at') }}</span></span></th>
                                <th class="min-w-[120px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('workflows.versions.columns.status') }}</span></span></th>
                                <th class="min-w-[140px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('workflows.versions.columns.total_requests') }}</span></span></th>
                                <th class="min-w-[140px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('workflows.versions.columns.active_requests') }}</span></span></th>
                                <th class="min-w-[100px] text-center"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($versions as $version)
                                <tr>
                                    <td><span class="fl-badge fl-badge-sm fl-badge-primary">{{ $version->version }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $version->created_at->format('M d, Y H:i') }}</span></td>
                                    <td>
                                        @if($version->isDraft())
                                            <span class="fl-badge fl-badge-sm fl-badge-warning">{{ __('workflows.versions.draft') }}</span>
                                        @elseif($version->is_current)
                                            <span class="fl-badge fl-badge-sm fl-badge-success">{{ __('workflows.versions.current') }}</span>
                                        @else
                                            <span class="fl-badge fl-badge-sm fl-badge-outline">{{ __('workflows.versions.superseded') }}</span>
                                        @endif
                                    </td>
                                    <td><span class="text-sm text-foreground">{{ $version->instances_count }}</span></td>
                                    <td><span class="text-sm text-foreground">{{ $version->active_instances_count }}</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('workflow-templates.show', $version) }}" class="fl-btn fl-btn-sm fl-btn-outline">
                                            <x-tabler-eye-filled />
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
