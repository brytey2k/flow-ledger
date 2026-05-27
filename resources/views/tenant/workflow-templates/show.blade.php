@extends('tenant.layouts.base')

@section('content')
<div class="kt-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ $workflowTemplate->name }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                @php $typeColors = [\App\Enums\Tenant\PaymentRequestType::Advance->value => 'kt-badge-primary', \App\Enums\Tenant\PaymentRequestType::Expense->value => 'kt-badge-success', \App\Enums\Tenant\PaymentRequestType::Retirement->value => 'kt-badge-warning']; @endphp
                <span class="kt-badge kt-badge-sm {{ $typeColors[$workflowTemplate->type] ?? 'kt-badge-outline' }}">
                    {{ ucfirst($workflowTemplate->type) }}
                </span>
                &bull; {{ $workflowTemplate->stages->count() }} {{ Str::plural('stage', $workflowTemplate->stages->count()) }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            @can(App\Enums\Tenant\PermissionKey::EditWorkflowTemplate->value)
                <a class="kt-btn kt-btn-outline" href="{{ route('workflow-templates.edit', $workflowTemplate) }}">
                    <i class="ki-filled ki-pencil"></i>
                    {{ __('workflows.edit_title') }}
                </a>
            @endcan
            <a class="kt-btn kt-btn-outline" href="{{ route('workflow-templates.index') }}">
                <i class="ki-filled ki-arrow-left"></i>
                {{ __('workflows.back') }}
            </a>
        </div>
    </div>
</div>

<div class="kt-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        {{-- Parallel Groups --}}
        <div class="kt-card">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('workflows.show.parallel_groups') }}</h3>
                <div class="text-xs text-muted-foreground">{{ __('workflows.show.parallel_groups_hint') }}</div>
            </div>
            <div class="kt-card-content p-5">
                @if($workflowTemplate->parallelGroups->isNotEmpty())
                    <div class="flex flex-wrap gap-3 mb-5">
                        @foreach($workflowTemplate->parallelGroups as $group)
                            <div class="flex items-center gap-2 rounded-lg border border-border px-3 py-2">
                                <span class="text-sm font-medium text-mono">{{ $group->name }}</span>
                                <span class="kt-badge kt-badge-sm {{ $group->require_all ? 'kt-badge-warning' : 'kt-badge-success' }}">
                                    {{ $group->require_all ? 'ALL must approve' : 'ANY one approves' }}
                                </span>
                                <form action="{{ route('workflow-templates.parallel-groups.destroy', [$workflowTemplate, $group]) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-muted-foreground hover:text-destructive" onclick="return confirm('{{ __('workflows.show.delete_group') }}')">
                                        <i class="ki-filled ki-cross text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('workflow-templates.parallel-groups.store', $workflowTemplate) }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <div>
                        <label class="kt-form-label block mb-1 text-xs" for="pg_name">Group Name</label>
                        <input id="pg_name" name="name" type="text" class="kt-input" placeholder="e.g. Finance & HR" />
                        @error('name')
                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="kt-form-label block mb-1 text-xs" for="pg_require_all">Logic</label>
                        <select id="pg_require_all" name="require_all" class="kt-select">
                            <option value="1">ALL must approve (AND)</option>
                            <option value="0">ANY one approves (OR)</option>
                        </select>
                    </div>
                    <button type="submit" class="kt-btn kt-btn-sm kt-btn-outline mt-5">
                        <i class="ki-filled ki-plus"></i>
                        {{ __('workflows.show.add_group') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Stages --}}
        <div class="kt-card kt-card-grid">
            <div class="kt-card-header">
                <h3 class="kt-card-title">{{ __('workflows.show.approval_stages') }}</h3>
                <div class="text-xs text-muted-foreground">{{ __('workflows.show.stages_hint') }}</div>
            </div>

            @if($workflowTemplate->stages->isEmpty())
                <div class="kt-card-content p-5 lg:p-7.5">
                    <div class="flex flex-col items-center justify-center py-8">
                        <i class="ki-filled ki-arrow-right-left text-5xl text-muted-foreground mb-3"></i>
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('workflows.show.no_stages') }}</p>
                    </div>
                </div>
            @else
                <div class="kt-card-table">
                    <div class="kt-scrollable-x-auto border-b border-border">
                        <table class="kt-table kt-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.show.columns.order') }}</span></span></th>
                                    <th class="min-w-[180px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.show.columns.stage_name') }}</span></span></th>
                                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.show.columns.roles') }}</span></span></th>
                                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.show.columns.parallel_group') }}</span></span></th>
                                    <th class="min-w-[130px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.show.columns.skip_below') }}</span></span></th>
                                    <th class="min-w-[160px]"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('workflows.show.columns.approver_scope') }}</span></span></th>
                                    <th class="min-w-[100px] text-center"><span class="kt-table-col"><span class="kt-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workflowTemplate->stages->sortBy('display_order') as $stage)
                                    <tr>
                                        <td><span class="kt-badge kt-badge-sm kt-badge-primary">{{ $stage->display_order }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $stage->name }}</span></td>
                                        <td>
                                            <div class="flex flex-wrap gap-1">
                                                @forelse($stage->roles as $role)
                                                    <span class="kt-badge kt-badge-sm kt-badge-outline">{{ $role->name }}</span>
                                                @empty
                                                    <span class="text-xs text-muted-foreground">—</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            @if($stage->parallelGroup)
                                                <div class="flex items-center gap-1">
                                                    <span class="text-sm text-foreground">{{ $stage->parallelGroup->name }}</span>
                                                    <span class="kt-badge kt-badge-sm {{ $stage->parallelGroup->require_all ? 'kt-badge-warning' : 'kt-badge-success' }}">
                                                        {{ $stage->parallelGroup->require_all ? __('workflows.show.and_label') : __('workflows.show.or_label') }}
                                                    </span>
                                                </div>
                                            @else
                                                <span class="text-xs text-muted-foreground">{{ __('workflows.show.sequential') }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($stage->skip_below_amount !== null)
                                                <span class="text-sm text-foreground">
                                                    &lt; {{ number_format($stage->skip_below_amount, 2) }}
                                                </span>
                                            @else
                                                <span class="text-xs text-muted-foreground">—</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="flex flex-wrap gap-1">
                                                @if($stage->scope_to_branch)
                                                    <span class="kt-badge kt-badge-sm kt-badge-info">{{ __('workflows.show.scope_branch') }}</span>
                                                @endif
                                                @if($stage->scope_to_department)
                                                    <span class="kt-badge kt-badge-sm kt-badge-warning">{{ __('workflows.show.scope_department') }}</span>
                                                @endif
                                                @if(!$stage->scope_to_branch && !$stage->scope_to_department)
                                                    <span class="text-xs text-muted-foreground">—</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('workflow-templates.stages.edit', [$workflowTemplate, $stage]) }}"
                                               class="kt-btn kt-btn-sm kt-btn-outline">
                                                <i class="ki-filled ki-pencil"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="kt-card-footer p-5">
                <a href="{{ route('workflow-templates.stages.create', $workflowTemplate) }}" class="kt-btn kt-btn-sm kt-btn-primary">
                    <i class="ki-filled ki-plus"></i>
                    {{ __('workflows.show.add_stage') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
