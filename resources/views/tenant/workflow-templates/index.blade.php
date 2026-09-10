@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ __('workflows.title') }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                {{ __('workflows.subtitle') }}
            </div>
        </div>
        @can(App\Enums\Tenant\PermissionKey::CreateWorkflowTemplate->value)
            <a class="sgh-btn sgh-btn-primary" href="{{ route('workflow-templates.create') }}">
                <x-tabler-plus-filled />
                {{ __('workflows.add_new') }}
            </a>
        @endcan
    </div>
</div>

<div class="sgh-container-fixed">
    <x-index-filters :action="route('workflow-templates.index')" :reset-url="route('workflow-templates.index')" :filters="$filters" search-placeholder="Search workflow templates…">
        <div class="flex flex-col gap-1">
            <label for="type" class="sgh-form-label">{{ __('common.columns.type') }}</label>
            <select id="type" name="type" class="sgh-select w-full">
                <option value="">{{ __('common.all_types') }}</option>
                <option value="advance" @selected(($filters['type'] ?? null) === 'advance')>{{ __('workflows.fields.type_advance') }}</option>
                <option value="expense" @selected(($filters['type'] ?? null) === 'expense')>{{ __('workflows.fields.type_expense') }}</option>
                <option value="retirement" @selected(($filters['type'] ?? null) === 'retirement')>{{ __('workflows.fields.type_retirement') }}</option>
            </select>
        </div>
        <div class="flex flex-col gap-1">
            <label for="branch_id" class="sgh-form-label">{{ __('common.columns.branch') }}</label>
            <select id="branch_id" name="branch_id" class="sgh-select w-full">
                <option value="">{{ __('common.all') }}</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) === $branch->id)>{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
    </x-index-filters>

    <div class="grid gap-5 lg:gap-7.5">
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('workflows.all') }}</h3>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                    {{ $templates->count() }} {{ Str::plural('Template', $templates->count()) }}
                </span>
            </div>

            @if($templates->isEmpty())
                <div class="sgh-card-content p-5 lg:p-7.5">
                    <div class="flex flex-col items-center justify-center py-12">
                        <x-tabler-file-text-filled class="text-6xl text-muted-foreground mb-4" />
                        <h3 class="text-lg font-medium text-foreground mb-2">{{ __('workflows.empty.heading') }}</h3>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('workflows.empty.subtext') }}</p>
                        @can(App\Enums\Tenant\PermissionKey::CreateWorkflowTemplate->value)
                            <a href="{{ route('workflow-templates.create') }}" class="sgh-btn sgh-btn-primary">
                                <x-tabler-plus-filled />
                                {{ __('workflows.buttons.add') }}
                            </a>
                        @endcan
                    </div>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[200px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.name') }}</span></span></th>
                                    <th class="min-w-[120px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.type') }}</span></span></th>
                                    <th class="min-w-[150px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.columns.branch') }}</span></span></th>
                                    <th class="min-w-[100px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.columns.stages') }}</span></span></th>
                                    <th class="min-w-[150px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.created') }}</span></span></th>
                                    <th class="min-w-[120px] text-center"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
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
                                                $typeColors = [\App\Enums\Tenant\PaymentRequestType::Advance->value => 'sgh-badge-primary', \App\Enums\Tenant\PaymentRequestType::Expense->value => 'sgh-badge-success', \App\Enums\Tenant\PaymentRequestType::Retirement->value => 'sgh-badge-warning'];
                                            @endphp
                                            <span class="sgh-badge sgh-badge-sm {{ $typeColors[$template->type] ?? 'sgh-badge-outline' }}">
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
                                                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ __('workflows.columns.master') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $template->stages_count }}</span>
                                        </td>
                                        <td>
                                            <span class="text-sm text-foreground">{{ $template->created_at->format('M d, Y') }}</span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('workflow-templates.show', $template) }}" class="sgh-btn sgh-btn-sm sgh-btn-outline">
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
