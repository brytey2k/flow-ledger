@extends('tenant.layouts.base')

@section('content')
<div class="fl-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('workflows.subtitle') }}
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateWorkflowTemplate->value)
            <a class="fl-btn fl-btn-primary" href="{{ route('workflow-templates.create') }}">
                <x-tabler-plus-filled />
                {{ __('workflows.add_new') }}
            </a>
        @endcan
    </div>
</div>

<div class="fl-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        <div class="fl-card fl-card-grid">
            <div class="fl-card-header">
                <h3 class="fl-card-title">{{ __('workflows.all') }}</h3>
                <span class="fl-badge fl-badge-sm fl-badge-outline">
                    {{ $templates->count() }} {{ Str::plural('Template', $templates->count()) }}
                </span>
            </div>

            @if($templates->isEmpty())
                <div class="fl-card-content p-5 lg:p-7.5">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-file-text-filled class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('workflows.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('workflows.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateWorkflowTemplate->value)
                            <a href="{{ route('workflow-templates.create') }}" class="fl-btn fl-btn-primary">
                                <x-tabler-plus-filled />
                                {{ __('workflows.buttons.add') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="fl-card-table">
                    <div class="fl-scrollable-x-auto border-b border-border">
                        <table class="fl-table fl-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.name') }}</span></span></th>
                                    <th class="min-w-[120px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.type') }}</span></span></th>
                                    <th class="min-w-[150px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('workflows.columns.branch') }}</span></span></th>
                                    <th class="min-w-[100px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('workflows.columns.stages') }}</span></span></th>
                                    <th class="min-w-[150px]"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.created') }}</span></span></th>
                                    <th class="min-w-[120px] text-center"><span class="fl-table-col"><span class="fl-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($templates as $template)
                                    <tr>
                                        <td>
                                            <a href="{{ route('workflow-templates.show', $template) }}" class="text-sm font-medium text-primary hover:underline">
                                                {{ $template->name }}
                                            </a>
                                        </td>
                                        <td>
                                            @php
                                                $typeColors = [\App\Enums\Tenant\PaymentRequestType::Advance->value => 'fl-badge-primary', \App\Enums\Tenant\PaymentRequestType::Expense->value => 'fl-badge-success', \App\Enums\Tenant\PaymentRequestType::Retirement->value => 'fl-badge-warning'];
                                            @endphp
                                            <span class="fl-badge fl-badge-sm {{ $typeColors[$template->type] ?? 'fl-badge-outline' }}">
                                                @if($template->type === 'advance') {{ __('workflows.fields.type_advance') }}
                                                @elseif($template->type === 'expense') {{ __('workflows.fields.type_expense') }}
                                                @elseif($template->type === 'retirement') {{ __('workflows.fields.type_retirement') }}
                                                @else {{ ucfirst($template->type) }}
                                                @endif
                                            </span>
                                        </td>
                                        <td>
                                            @if($template->branch)
                                                <span class="text-sm text-foreground">{{ $template->branch->name }}</span>
                                            @else
                                                <span class="fl-badge fl-badge-sm fl-badge-outline">{{ __('workflows.columns.master') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="fl-badge fl-badge-sm fl-badge-outline">{{ $template->stages_count }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $template->created_at->format('M d, Y') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('workflow-templates.show', $template) }}" class="fl-btn fl-btn-sm fl-btn-outline">
                                                <x-tabler-settings-filled />
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
