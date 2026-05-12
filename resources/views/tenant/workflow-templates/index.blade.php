@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('workflows.subtitle') }}
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateWorkflowTemplate->value)
            <a class="kt-btn kt-btn-primary" href="{{ route('workflow-templates.create') }}">
                <i class="ki-filled ki-plus"></i>
                {{ __('workflows.add_new') }}
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
                <h3 class="kt-card-title">{{ __('workflows.all') }}</h3>
                <span class="kt-badge kt-badge-sm kt-badge-outline">
                    {{ $templates->count() }} {{ Str::plural('Template', $templates->count()) }}
                </span>
            </div>

            @if($templates->isEmpty())
                <div class="kt-card-content p-5 lg:p-7.5">
                    <div class="flex flex-col items-center justify-center py-12">
                        <i class="ki-filled ki-document text-6xl text-muted-foreground mb-4"></i>
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('workflows.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('workflows.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateWorkflowTemplate->value)
                            <a href="{{ route('workflow-templates.create') }}" class="kt-btn kt-btn-primary">
                                <i class="ki-filled ki-plus"></i>
                                {{ __('workflows.buttons.add') }}
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
                                    <th class="min-w-[200px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.name') }}</span></span></th>
                                    <th class="min-w-[120px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.type') }}</span></span></th>
                                    <th class="min-w-[100px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.columns.stages') }}</span></span></th>
                                    <th class="min-w-[150px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.created') }}</span></span></th>
                                    <th class="min-w-[120px] text-center"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
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
                                                @if($template->type === 'advance') {{ __('workflows.fields.type_advance') }}
                                                @elseif($template->type === 'expense') {{ __('workflows.fields.type_expense') }}
                                                @elseif($template->type === 'retirement') {{ __('workflows.fields.type_retirement') }}
                                                @else {{ ucfirst($template->type) }}
                                                @endif
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
                                                {{ __('workflows.buttons.configure') }}
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
