<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\WorkflowForkResult;
use App\Enums\Tenant\WorkflowTemplateStatus;
use App\Models\Tenant\WorkflowInstanceStageRecoveryRole;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class WorkflowTemplateVersioningService
{
    public function shouldFork(WorkflowTemplate $template, bool $isStructuralChange): bool
    {
        return $isStructuralChange && $template->hasActiveInstances();
    }

    public function draftFor(WorkflowTemplate $template): WorkflowTemplate|null
    {
        if ($template->isDraft()) {
            return $template;
        }

        return WorkflowTemplate::where('template_group_id', $template->template_group_id)
            ->where('status', WorkflowTemplateStatus::Draft->value)
            ->first();
    }

    public function forkDraft(WorkflowTemplate $current): WorkflowForkResult
    {
        return DB::transaction(function () use ($current): WorkflowForkResult {
            /** @var WorkflowTemplate $current */
            $current = WorkflowTemplate::lockForUpdate()->findOrFail($current->id);
            $current->loadMissing(['parallelGroups', 'stages.roles', 'stages.fallbackRoles']);

            /** @var WorkflowTemplate $newTemplate */
            $newTemplate = WorkflowTemplate::create([
                'name' => $current->name,
                'type' => $current->type,
                'branch_id' => $current->branch_id,
                'template_group_id' => $current->template_group_id,
                'version' => $current->version + 1,
                'is_current' => false,
                'status' => WorkflowTemplateStatus::Draft->value,
            ]);

            $groupIdMap = [];

            foreach ($current->parallelGroups as $group) {
                /** @var WorkflowParallelGroup $newGroup */
                $newGroup = $newTemplate->parallelGroups()->create([
                    'name' => $group->name,
                    'require_all' => $group->require_all,
                ]);

                $groupIdMap[$group->id] = $newGroup->id;
            }

            $stageIdMap = [];

            foreach ($current->stages as $stage) {
                $lineageId = $this->ensureStageLineage($stage);

                /** @var WorkflowStage $newStage */
                $newStage = $newTemplate->stages()->create([
                    'lineage_id' => $lineageId,
                    'name' => $stage->name,
                    'display_order' => $stage->display_order,
                    'skip_below_amount' => $stage->skip_below_amount,
                    'parallel_group_id' => $stage->parallel_group_id !== null
                        ? $groupIdMap[$stage->parallel_group_id]
                        : null,
                    'scope_to_department' => $stage->scope_to_department,
                    'scope_to_branch' => $stage->scope_to_branch,
                    'allow_send_back' => $stage->allow_send_back,
                ]);
                $newStage->roles()->sync($stage->roles()->pluck('roles.id'));
                $newStage->fallbackRoles()->sync($stage->fallbackRoles()->pluck('roles.id'));

                $stageIdMap[$stage->id] = $newStage->id;
            }

            return new WorkflowForkResult($newTemplate, $stageIdMap, $groupIdMap);
        });
    }

    public function ensureStageLineage(WorkflowStage $stage): string
    {
        if ($stage->lineage_id !== null) {
            return $stage->lineage_id;
        }

        $lineageId = (string) Str::uuid();
        $stage->update(['lineage_id' => $lineageId]);

        return $lineageId;
    }

    public function publish(WorkflowTemplate $draft): void
    {
        if (! $draft->isDraft()) {
            throw new InvalidArgumentException('Only a draft version can be published.');
        }

        DB::transaction(function () use ($draft): void {
            /** @var WorkflowTemplate $draft */
            $draft = WorkflowTemplate::lockForUpdate()->findOrFail($draft->id);

            WorkflowTemplate::where('template_group_id', $draft->template_group_id)
                ->where('is_current', true)
                ->update(['is_current' => false]);

            $draft->update(['is_current' => true, 'status' => WorkflowTemplateStatus::Published->value]);
            $this->markRecoveryRepairsPublished($draft);
        });
    }

    public function discard(WorkflowTemplate $draft): void
    {
        if (! $draft->isDraft()) {
            throw new InvalidArgumentException('Only a draft version can be discarded.');
        }

        DB::transaction(function () use ($draft): void {
            WorkflowInstanceStageRecoveryRole::query()
                ->where('prepared_workflow_template_id', $draft->id)
                ->where('template_repair_status', 'draft')
                ->update([
                    'prepared_workflow_template_id' => null,
                    'template_repair_status' => 'required',
                    'template_repair_prepared_at' => null,
                ]);

            $draft->delete();
        });
    }

    private function markRecoveryRepairsPublished(WorkflowTemplate $template): void
    {
        WorkflowInstanceStageRecoveryRole::query()
            ->where('prepared_workflow_template_id', $template->id)
            ->where('template_repair_status', 'draft')
            ->update([
                'prepared_workflow_template_id' => null,
                'template_repair_status' => 'required',
                'template_repair_prepared_at' => null,
            ]);

        $template->loadMissing(['stages.roles', 'stages.fallbackRoles']);

        foreach ($template->stages as $stage) {
            if ($stage->lineage_id === null) {
                continue;
            }

            $roleIds = $stage->roles->merge($stage->fallbackRoles)->pluck('id')->unique();

            if ($roleIds->isEmpty()) {
                continue;
            }

            WorkflowInstanceStageRecoveryRole::query()
                ->whereIn('role_id', $roleIds)
                ->whereHas('instanceStage.stage', fn($query) => $query->where('lineage_id', $stage->lineage_id))
                ->update([
                    'prepared_workflow_template_id' => $template->id,
                    'template_repair_status' => 'published',
                    'template_repair_published_at' => now(),
                ]);
        }
    }
}
