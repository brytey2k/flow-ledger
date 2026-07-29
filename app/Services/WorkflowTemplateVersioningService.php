<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\WorkflowForkResult;
use App\Enums\Tenant\WorkflowTemplateStatus;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Support\Facades\DB;
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
                /** @var WorkflowStage $newStage */
                $newStage = $newTemplate->stages()->create([
                    'name' => $stage->name,
                    'display_order' => $stage->display_order,
                    'skip_below_amount' => $stage->skip_below_amount,
                    'parallel_group_id' => $stage->parallel_group_id !== null
                        ? $groupIdMap[$stage->parallel_group_id]
                        : null,
                    'scope_to_department' => $stage->scope_to_department,
                    'scope_to_branch' => $stage->scope_to_branch,
                ]);
                $newStage->roles()->sync($stage->roles()->pluck('roles.id'));

                $stageIdMap[$stage->id] = $newStage->id;
            }

            return new WorkflowForkResult($newTemplate, $stageIdMap, $groupIdMap);
        });
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
        });
    }

    public function discard(WorkflowTemplate $draft): void
    {
        if (! $draft->isDraft()) {
            throw new InvalidArgumentException('Only a draft version can be discarded.');
        }

        $draft->delete();
    }
}
