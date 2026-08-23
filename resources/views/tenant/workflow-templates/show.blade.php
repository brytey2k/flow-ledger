@extends('tenant.layouts.base')

@section('content')
<div class="sgh-container-fixed">
    <div class="flex flex-wrap items-center justify-between gap-5 pb-7.5 lg:items-end">
        <div class="flex flex-col justify-center gap-2">
            <h1 class="text-xl font-medium leading-none text-mono">{{ $workflowTemplate->name }}</h1>
            <div class="flex items-center gap-2 text-sm font-normal text-secondary-foreground">
                @php $typeColors = [\App\Enums\Tenant\PaymentRequestType::Advance->value => 'sgh-badge-primary', \App\Enums\Tenant\PaymentRequestType::Expense->value => 'sgh-badge-success', \App\Enums\Tenant\PaymentRequestType::Retirement->value => 'sgh-badge-warning']; @endphp
                <span class="sgh-badge sgh-badge-sm {{ $typeColors[$workflowTemplate->type] ?? 'sgh-badge-outline' }}">
                    {{ ucfirst($workflowTemplate->type) }}
                </span>
                <span class="sgh-badge sgh-badge-sm sgh-badge-outline">
                    {{ __('workflows.show.version_badge', ['number' => $workflowTemplate->version]) }}
                </span>
                @if($workflowTemplate->isDraft())
                    <span class="sgh-badge sgh-badge-sm sgh-badge-warning">{{ __('workflows.show.draft_badge') }}</span>
                @elseif(!$workflowTemplate->is_current)
                    <span class="sgh-badge sgh-badge-sm sgh-badge-warning">{{ __('workflows.show.superseded_badge') }}</span>
                @endif
                &bull; {{ $workflowTemplate->stages->count() }} {{ Str::plural('stage', $workflowTemplate->stages->count()) }}
            </div>
        </div>
        <div class="flex items-center gap-2.5">
            @if($workflowTemplate->isDraft())
                @can(App\Enums\Tenant\PermissionKey::EditWorkflowTemplate->value)
                    <form method="POST" action="{{ route('workflow-templates.draft.discard', $workflowTemplate) }}" class="inline">
                        @csrf @method('DELETE')
                        <button type="submit" class="sgh-btn sgh-btn-outline sgh-btn-destructive" onclick="return confirm('{{ __('workflows.show.confirm_discard') }}')">
                            <x-tabler-trash-filled />
                            {{ __('workflows.show.discard_draft') }}
                        </button>
                    </form>
                    <form method="POST" action="{{ route('workflow-templates.publish', $workflowTemplate) }}" class="inline">
                        @csrf
                        <button type="submit" class="sgh-btn sgh-btn-primary" onclick="return confirm('{{ __('workflows.show.confirm_publish') }}')">
                            <x-tabler-check-filled />
                            {{ __('workflows.show.publish') }}
                        </button>
                    </form>
                @endcan
            @endif
            <a class="sgh-btn sgh-btn-outline" href="{{ route('workflow-templates.versions', $workflowTemplate) }}">
                <x-tabler-clock-filled />
                {{ __('workflows.show.version_history') }}
            </a>
            @can(App\Enums\Tenant\PermissionKey::EditWorkflowTemplate->value)
                <a class="sgh-btn sgh-btn-outline" href="{{ route('workflow-templates.edit', $workflowTemplate) }}">
                    <x-tabler-pencil-filled />
                    {{ __('workflows.edit_title') }}
                </a>
            @endcan
            <a class="sgh-btn sgh-btn-outline" href="{{ route('workflow-templates.index') }}">
                <x-tabler-arrow-left />
                {{ __('workflows.back') }}
            </a>
        </div>
    </div>
    @if($workflowTemplate->isDraft())
        <div class="sgh-alert sgh-alert-light sgh-alert-warning mt-5 mb-5 lg:mb-7.5">
            <span class="sgh-alert-icon"><x-tabler-info-square-rounded-filled class="text-xl" /></span>
            <div class="sgh-alert-content">
                <div class="sgh-alert-description">{{ __('workflows.show.draft_banner') }}</div>
            </div>
        </div>
    @endif
</div>

<div class="sgh-container-fixed">
    <div class="grid gap-5 lg:gap-7.5">
        {{-- Parallel Groups --}}
        <div class="sgh-card">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('workflows.show.parallel_groups') }}</h3>
                <div class="text-xs text-muted-foreground">{{ __('workflows.show.parallel_groups_hint') }}</div>
            </div>
            <div class="sgh-card-content p-5">
                @if($workflowTemplate->parallelGroups->isNotEmpty())
                    <div class="flex flex-wrap gap-3 mb-5">
                        @foreach($workflowTemplate->parallelGroups as $group)
                            <div class="flex items-center gap-2 rounded-lg border border-border px-3 py-2">
                                <span class="text-sm font-medium text-mono">{{ $group->name }}</span>
                                <span class="sgh-badge sgh-badge-sm {{ $group->require_all ? 'sgh-badge-warning' : 'sgh-badge-success' }}">
                                    {{ $group->require_all ? 'ALL must approve' : 'ANY one approves' }}
                                </span>
                                <form action="{{ route('workflow-templates.parallel-groups.destroy', [$workflowTemplate, $group]) }}" method="POST" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-muted-foreground hover:text-destructive" onclick="return confirm('{{ __('workflows.show.delete_group') }}')">
                                        <x-tabler-x-filled class="text-xs" />
                                    </button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                @endif

                <form method="POST" action="{{ route('workflow-templates.parallel-groups.store', $workflowTemplate) }}" class="flex flex-wrap items-start gap-3">
                    @csrf
                    <div>
                        <label class="sgh-form-label block mb-1 text-xs" for="pg_name">Group Name</label>
                        <input id="pg_name" name="name" type="text" class="sgh-input" placeholder="e.g. Finance & HR" />
                        @error('name')
                            <p class="mt-1 text-xs text-destructive">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="sgh-form-label block mb-1 text-xs" for="pg_require_all">Logic</label>
                        <select id="pg_require_all" name="require_all" class="sgh-select">
                            <option value="1">ALL must approve (AND)</option>
                            <option value="0">ANY one approves (OR)</option>
                        </select>
                    </div>
                    <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-outline mt-5">
                        <x-tabler-plus-filled />
                        {{ __('workflows.show.add_group') }}
                    </button>
                </form>
            </div>
        </div>

        {{-- Stages --}}
        <div class="sgh-card sgh-card-grid">
            <div class="sgh-card-header">
                <h3 class="sgh-card-title">{{ __('workflows.show.approval_stages') }}</h3>
                <div class="text-xs text-muted-foreground">{{ __('workflows.show.stages_hint') }}</div>
            </div>

            @if($workflowTemplate->stages->isEmpty())
                <div class="sgh-card-content p-5 lg:p-7.5">
                    <div class="flex flex-col items-center justify-center py-8">
                        <x-tabler-arrows-exchange class="text-5xl text-muted-foreground mb-3" />
                        <p class="text-sm text-secondary-foreground mb-4">{{ __('workflows.show.no_stages') }}</p>
                    </div>
                </div>
            @else
                <div class="sgh-card-table">
                    <div class="sgh-scrollable-x-auto border-b border-border">
                        <table class="sgh-table sgh-table-border">
                            <thead>
                                <tr>
                                    <th class="min-w-[60px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.show.columns.order') }}<x-help-tooltip :text="__('workflows.show.column_tips.order')" /></span></span></th>
                                    <th class="min-w-[180px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.show.columns.stage_name') }}<x-help-tooltip :text="__('workflows.show.column_tips.stage_name')" /></span></span></th>
                                    <th class="min-w-[160px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.show.columns.roles') }}<x-help-tooltip :text="__('workflows.show.column_tips.roles')" /></span></span></th>
                                    <th class="min-w-[160px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.show.columns.parallel_group') }}<x-help-tooltip :text="__('workflows.show.column_tips.parallel_group')" /></span></span></th>
                                    <th class="min-w-[130px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.show.columns.skip_below') }}<x-help-tooltip :text="__('workflows.show.column_tips.skip_below')" /></span></span></th>
                                    <th class="min-w-[160px]"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('workflows.show.columns.approver_scope') }}<x-help-tooltip :text="__('workflows.show.column_tips.approver_scope')" /></span></span></th>
                                    <th class="min-w-[100px] text-center"><span class="sgh-table-col"><span class="sgh-table-col-label">{{ __('common.columns.actions') }}</span></span></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($workflowTemplate->stages->sortBy('display_order') as $stage)
                                    <tr>
                                        <td><span class="sgh-badge sgh-badge-sm sgh-badge-primary">{{ $stage->display_order }}</span></td>
                                        <td><span class="text-sm font-medium text-mono">{{ $stage->name }}</span></td>
                                        <td>
                                            <div class="flex flex-wrap gap-1">
                                                @forelse($stage->roles as $role)
                                                    <span class="sgh-badge sgh-badge-sm sgh-badge-outline">{{ $role->name }}</span>
                                                @empty
                                                    <span class="text-xs text-muted-foreground">—</span>
                                                @endforelse
                                            </div>
                                        </td>
                                        <td>
                                            @if($stage->parallelGroup)
                                                <div class="flex items-center gap-1">
                                                    <span class="text-sm text-foreground">{{ $stage->parallelGroup->name }}</span>
                                                    <span class="sgh-badge sgh-badge-sm {{ $stage->parallelGroup->require_all ? 'sgh-badge-warning' : 'sgh-badge-success' }}">
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
                                                    <span class="sgh-badge sgh-badge-sm sgh-badge-info">{{ __('workflows.show.scope_branch') }}</span>
                                                @endif
                                                @if($stage->scope_to_department)
                                                    <span class="sgh-badge sgh-badge-sm sgh-badge-warning">{{ __('workflows.show.scope_department') }}</span>
                                                @endif
                                                @if(!$stage->scope_to_branch && !$stage->scope_to_department)
                                                    <span class="text-xs text-muted-foreground">—</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('workflow-templates.stages.edit', [$workflowTemplate, $stage]) }}"
                                               class="sgh-btn sgh-btn-sm sgh-btn-outline">
                                                <x-tabler-pencil-filled />
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="sgh-card-footer p-5">
                <a href="{{ route('workflow-templates.stages.create', $workflowTemplate) }}" class="sgh-btn sgh-btn-sm sgh-btn-primary">
                    <x-tabler-plus-filled />
                    {{ __('workflows.show.add_stage') }}
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
