@props(['instanceStage'])

@if($instanceStage->status === 'blocked')
    <div class="mt-2 flex flex-col gap-2">
        <span class="text-xs text-warning">{{ __('workflows.separation.no_independent_approver') }}</span>
        @can(\App\Enums\Tenant\PermissionKey::EditWorkflowTemplate->value)
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('approvals.retry', $instanceStage) }}">
                    @csrf
                    <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-outline">
                        {{ __('workflows.separation.retry') }}
                    </button>
                </form>
                <a href="{{ route('approvals.recovery.create', $instanceStage) }}" class="sgh-btn sgh-btn-sm sgh-btn-warning">
                    {{ __('workflows.separation.add_recovery_fallback') }}
                </a>
            </div>
        @else
            <span class="text-xs text-muted-foreground">{{ __('workflows.separation.contact_workflow_admin') }}</span>
        @endcan
    </div>
@endif

@if($instanceStage->recoveryRoles->isNotEmpty())
    @php
        $requiredRecoveryRoles = $instanceStage->recoveryRoles
            ->filter(fn($role) => $role->pivot->template_repair_status === 'required');
        $draftRecoveryRole = $instanceStage->recoveryRoles
            ->first(fn($role) => $role->pivot->template_repair_status === 'draft');
        $templateRepairComplete = $requiredRecoveryRoles->isEmpty() && $draftRecoveryRole === null;
    @endphp
    <div class="mt-3 rounded-lg border p-3 {{ $templateRepairComplete ? 'border-success/40 bg-success/10' : 'border-warning/40 bg-warning/10' }}">
        <div class="flex gap-2">
            @if($templateRepairComplete)
                <x-tabler-check-filled class="mt-0.5 shrink-0 text-success" />
            @else
                <x-tabler-alert-triangle class="mt-0.5 shrink-0 text-warning" />
            @endif
            <div class="flex flex-col gap-2">
                @if($requiredRecoveryRoles->isNotEmpty())
                    <div>
                        <div class="text-xs font-medium text-mono">{{ __('workflows.separation.template_repair_required_heading') }}</div>
                        <p class="mt-1 text-xs text-secondary-foreground">
                            {{ __('workflows.separation.template_repair_required_body') }}
                        </p>
                    </div>
                    @can(\App\Enums\Tenant\PermissionKey::EditWorkflowTemplate->value)
                        <div class="flex flex-wrap gap-2">
                            @foreach($requiredRecoveryRoles as $recoveryRole)
                                <form method="POST" action="{{ route('approvals.recovery.template', $instanceStage) }}">
                                    @csrf
                                    <input type="hidden" name="role_id" value="{{ $recoveryRole->id }}" />
                                    <button type="submit" class="sgh-btn sgh-btn-sm sgh-btn-warning">
                                        {{ __('workflows.separation.update_main_workflow', ['role' => $recoveryRole->name]) }}
                                    </button>
                                </form>
                            @endforeach
                        </div>
                    @else
                        <p class="text-xs text-muted-foreground">{{ __('workflows.separation.contact_workflow_admin_for_template') }}</p>
                    @endcan
                @elseif($draftRecoveryRole !== null)
                    <div>
                        <div class="text-xs font-medium text-mono">{{ __('workflows.separation.template_repair_draft_heading') }}</div>
                        <p class="mt-1 text-xs text-secondary-foreground">{{ __('workflows.separation.template_repair_draft_body') }}</p>
                    </div>
                    @can(\App\Enums\Tenant\PermissionKey::AccessWorkflowTemplates->value)
                        <a href="{{ route('workflow-templates.show', $draftRecoveryRole->pivot->prepared_workflow_template_id) }}" class="sgh-btn sgh-btn-sm sgh-btn-warning w-fit">
                            {{ __('workflows.separation.review_workflow_draft') }}
                        </a>
                    @endcan
                @else
                    <div>
                        <div class="text-xs font-medium text-mono">{{ __('workflows.separation.template_repair_complete_heading') }}</div>
                        <p class="mt-1 text-xs text-secondary-foreground">{{ __('workflows.separation.template_repair_complete_body') }}</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endif
