<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Role;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowInstanceStageRecoveryRole;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WorkflowStageRecoveryService
{
    public function __construct(
        private readonly WorkflowApproverResolver $approvers,
        private readonly NotificationService $notifications,
        private readonly WorkflowTemplateVersioningService $versioning,
    ) {}

    public function applyAndRetry(
        WorkflowInstanceStage $instanceStage,
        Role $role,
        User $actor,
        string $reason,
    ): bool {
        $activated = DB::transaction(function () use ($instanceStage, $role, $actor, $reason): bool {
            WorkflowInstance::lockForUpdate()->findOrFail($instanceStage->workflow_instance_id);
            /** @var WorkflowInstanceStage $fresh */
            $fresh = WorkflowInstanceStage::lockForUpdate()->findOrFail($instanceStage->id);

            if (! $fresh->isBlocked()) {
                throw ValidationException::withMessages([
                    'role_id' => __('workflows.separation.recovery_stage_not_blocked'),
                ]);
            }

            WorkflowInstanceStageRecoveryRole::updateOrCreate(
                [
                    'workflow_instance_stage_id' => $fresh->id,
                    'role_id' => $role->id,
                ],
                [
                    'applied_by_user_id' => $actor->id,
                    'reason' => $reason,
                ],
            );

            /** @var WorkflowInstance $instance */
            $instance = $fresh->instance;
            /** @var WorkflowStage $stage */
            $stage = $fresh->stage;
            /** @var Model $subject */
            $subject = $instance->workflowable;
            activity()
                ->performedOn($subject)
                ->causedBy($actor)
                ->event('workflow.approver_recovery_applied')
                ->withProperties([
                    'workflow_instance_stage_id' => $fresh->id,
                    'workflow_stage_id' => $fresh->workflow_stage_id,
                    'stage' => $stage->name,
                    'role_id' => $role->id,
                    'role' => $role->name,
                    'reason' => $reason,
                    'template_update_required' => true,
                ])
                ->log('One-request fallback role applied');

            return $this->approvers->resolvePool($fresh);
        });

        if ($activated) {
            $instanceStage->refresh();
            $this->notifications->notifyStageApprovers($instanceStage);
        }

        return $activated;
    }

    public function prepareTemplateRepair(
        WorkflowInstanceStage $instanceStage,
        Role $role,
        User $actor,
    ): WorkflowTemplate {
        return DB::transaction(function () use ($instanceStage, $role, $actor): WorkflowTemplate {
            /** @var WorkflowInstanceStage $fresh */
            $fresh = WorkflowInstanceStage::lockForUpdate()->findOrFail($instanceStage->id);

            /** @var WorkflowInstanceStageRecoveryRole|null $assignment */
            $assignment = $fresh->recoveryAssignments()->where('role_id', $role->id)->first();
            if (! $assignment instanceof WorkflowInstanceStageRecoveryRole) {
                throw ValidationException::withMessages([
                    'role_id' => __('workflows.separation.template_role_not_recovery'),
                ]);
            }

            /** @var WorkflowStage $sourceStage */
            $sourceStage = $fresh->stage()->lockForUpdate()->firstOrFail();
            /** @var WorkflowTemplate $sourceTemplate */
            $sourceTemplate = $sourceStage->template;
            $lineageId = $this->versioning->ensureStageLineage($sourceStage);

            /** @var WorkflowTemplate $currentTemplate */
            $currentTemplate = WorkflowTemplate::query()
                ->where('template_group_id', $sourceTemplate->template_group_id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->firstOrFail();

            $currentStage = $this->matchingStage($currentTemplate, $sourceStage, $lineageId);

            if ($this->stageUsesRole($currentStage, $role)) {
                $assignment->update([
                    'prepared_workflow_template_id' => $currentTemplate->id,
                    'template_repair_status' => 'published',
                    'template_repair_prepared_at' => now(),
                    'template_repair_published_at' => now(),
                ]);
                $this->logTemplateRepair($fresh, $sourceStage, $currentStage, $currentTemplate, $role, $actor, 'published');

                return $currentTemplate;
            }

            $draft = $this->versioning->draftFor($currentTemplate);

            if ($draft === null) {
                $fork = $this->versioning->forkDraft($currentTemplate);
                $draft = $fork->newTemplate;
                /** @var WorkflowStage $draftStage */
                $draftStage = WorkflowStage::findOrFail($fork->stageIdMap[$currentStage->id]);
            } else {
                $draftStage = $this->matchingStage($draft, $currentStage, $lineageId);
            }

            if (! $draftStage->roles()->whereKey($role->id)->exists()) {
                $draftStage->fallbackRoles()->syncWithoutDetaching([$role->id]);
            }

            $assignment->update([
                'prepared_workflow_template_id' => $draft->id,
                'template_repair_status' => 'draft',
                'template_repair_prepared_at' => now(),
                'template_repair_published_at' => null,
            ]);

            $this->logTemplateRepair($fresh, $sourceStage, $draftStage, $draft, $role, $actor, 'draft');

            return $draft;
        });
    }

    private function matchingStage(
        WorkflowTemplate $template,
        WorkflowStage $sourceStage,
        string $lineageId,
    ): WorkflowStage {
        if ($template->id === $sourceStage->workflow_template_id) {
            return $sourceStage;
        }

        /** @var WorkflowStage|null $matchingStage */
        $matchingStage = $template->stages()->where('lineage_id', $lineageId)->first();

        if ($matchingStage instanceof WorkflowStage) {
            return $matchingStage;
        }

        $structuralMatches = $template->stages()
            ->where('name', $sourceStage->name)
            ->where('display_order', $sourceStage->display_order)
            ->get();

        if ($structuralMatches->count() !== 1) {
            throw ValidationException::withMessages([
                'role_id' => __('workflows.separation.template_stage_not_found'),
            ]);
        }

        /** @var WorkflowStage $matchingStage */
        $matchingStage = $structuralMatches->first();

        if ($matchingStage->lineage_id !== null && $matchingStage->lineage_id !== $lineageId) {
            throw ValidationException::withMessages([
                'role_id' => __('workflows.separation.template_stage_not_found'),
            ]);
        }

        $matchingStage->update(['lineage_id' => $lineageId]);

        return $matchingStage;
    }

    private function stageUsesRole(WorkflowStage $stage, Role $role): bool
    {
        return $stage->roles()->whereKey($role->id)->exists()
            || $stage->fallbackRoles()->whereKey($role->id)->exists();
    }

    private function logTemplateRepair(
        WorkflowInstanceStage $instanceStage,
        WorkflowStage $sourceStage,
        WorkflowStage $targetStage,
        WorkflowTemplate $template,
        Role $role,
        User $actor,
        string $status,
    ): void {
        /** @var WorkflowInstance $instance */
        $instance = $instanceStage->instance;
        /** @var Model $subject */
        $subject = $instance->workflowable;
        activity()
            ->performedOn($subject)
            ->causedBy($actor)
            ->event('workflow.template_repair_prepared')
            ->withProperties([
                'workflow_instance_stage_id' => $instanceStage->id,
                'source_workflow_stage_id' => $sourceStage->id,
                'target_workflow_stage_id' => $targetStage->id,
                'workflow_template_id' => $template->id,
                'workflow_template_version' => $template->version,
                'role_id' => $role->id,
                'role' => $role->name,
                'template_repair_status' => $status,
            ])
            ->log('Workflow fallback repair prepared');
    }
}
