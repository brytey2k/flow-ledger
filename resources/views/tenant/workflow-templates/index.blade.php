@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">Workflow Templates</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                Configure approval workflows for requests
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateWorkflowTemplate->value)
            <a class="kt-btn kt-btn-primary" href="{{ route('workflow-templates.create') }}">
                <i class="ki-filled ki-plus"></i>
                New Template
            </a>
        @endcan
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        @if(session('success'))
            <div class="kt-alert kt-alert-success">
                <i class="ki-filled ki-check-circle"></i>
                {{ session('success') }}
            </div>
        @endif

        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">All Templates</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">
                    {{ $templates->count() }} {{ Str::plural('Template', $templates->count()) }}
                </span>
            </div>

            @if($templates->isEmpty())
                <div class="kt-card-content p-5 lg:p-7.5">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-document text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">No templates yet</h3>
                        <p class="text-sm text-secondary-foreground mb-4">Create your first workflow template to enable approvals</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateWorkflowTemplate->value)
                            <a href="{{ route('workflow-templates.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                New Template
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">Name</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">Type</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">Stages</span></span></th>
                                    <th class="min-w-[150px]"><span class="kt-table-col"><span class="kt-table-col-label">Created</span></span></th>
                                    <th class="min-w-[120px] text-center"><span class="kt-table-col"><span class="kt-table-col-label">Actions</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <a href="{{ route('workflow-templates.show', $template) }}" class="text-sm font-medium text-mono hover:underline">
                                                {{ $template->name }}
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                $typeColors = ['advance' => 'kt-badge-primary', 'expense' => 'kt-badge-success', 'retirement' => 'kt-badge-warning'];
                                            @endphp
                                            <span class="kt-badge kt-badge-sm {{ $typeColors[$template->type] ?? 'kt-badge-outline' }}">
                                                {{ ucfirst($template->type) }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $template->stages_count }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $template->created_at->format('M d, Y') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('workflow-templates.show', $template) }}" class="kt-btn kt-btn-sm kt-btn-outline">
                                                <i class="ki-filled ki-setting-2"></i>
                                                Configure
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
