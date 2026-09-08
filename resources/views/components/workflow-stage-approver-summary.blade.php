@props(['instanceStage', 'eligibility' => null])

@if($eligibility !== null)
    @php
        $isFallback = $instanceStage->approver_pool === \App\Services\WorkflowApproverResolver::FallbackPool;
        $configuredRoles = $isFallback
            ? $instanceStage->stage->fallbackRoles
            : $instanceStage->stage->roles;
    @endphp

    <div class="mt-1 flex flex-col gap-1.5 text-xs">
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-secondary-foreground">
            @if($configuredRoles->isNotEmpty())
                <span>
                    {{ __($isFallback ? 'workflows.separation.fallback_approvers' : 'workflows.separation.primary_approvers', [
                        'roles' => $configuredRoles->pluck('name')->join(', '),
                    ]) }}
                </span>
            @endif
            @if($instanceStage->recoveryRoles->isNotEmpty())
                <span class="sgh-badge sgh-badge-sm sgh-badge-warning">
                    {{ __('workflows.separation.recovery_approvers', [
                        'roles' => $instanceStage->recoveryRoles->pluck('name')->join(', '),
                    ]) }}
                </span>
            @endif
        </div>

        <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
            <span class="font-medium text-mono">
                {{ trans_choice('workflows.separation.eligible_approver_count', $eligibility['eligible_count'], [
                    'count' => $eligibility['eligible_count'],
                ]) }}
            </span>
            @can(\App\Enums\Tenant\PermissionKey::EditWorkflowTemplate->value)
                <a
                    href="{{ route('approvals.eligible-approvers', $instanceStage) }}"
                    class="font-medium text-primary hover:underline focus-visible:outline-2 focus-visible:outline-offset-2"
                >
                    {{ __('workflows.separation.view_eligible_approvers') }}
                </a>
            @endcan
        </div>

        <span class="{{ $eligibility['can_current_user_act'] ? 'text-success' : 'text-muted-foreground' }}">
            {{ __($eligibility['can_current_user_act'] ? 'workflows.separation.you_can_act' : 'workflows.separation.you_cannot_act') }}
        </span>
    </div>
@endif
