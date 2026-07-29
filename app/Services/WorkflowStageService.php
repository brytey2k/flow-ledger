<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\WorkflowStageDto;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Support\Facades\DB;

class WorkflowStageService
{
    public function create(WorkflowTemplate $template, WorkflowStageDto $dto): WorkflowStage
    {
        return DB::transaction(function () use ($template, $dto): WorkflowStage {
            /** @var WorkflowStage $stage */
            $stage = $template->stages()->create([
                'name' => $dto->name,
                'display_order' => $dto->displayOrder,
                'skip_below_amount' => $dto->skipBelowAmount,
                'parallel_group_id' => $dto->parallelGroupId,
                'scope_to_department' => $dto->scopeToDepartment,
                'scope_to_branch' => $dto->scopeToBranch,
            ]);
            $stage->roles()->sync($dto->roleIds);
            $this->syncParallelGroupDisplayOrder($stage);

            return $stage;
        });
    }

    public function update(WorkflowStage $stage, WorkflowStageDto $dto): void
    {
        DB::transaction(function () use ($stage, $dto): void {
            $stage->update([
                'name' => $dto->name,
                'display_order' => $dto->displayOrder,
                'skip_below_amount' => $dto->skipBelowAmount,
                'parallel_group_id' => $dto->parallelGroupId,
                'scope_to_department' => $dto->scopeToDepartment,
                'scope_to_branch' => $dto->scopeToBranch,
            ]);
            $stage->roles()->sync($dto->roleIds);
            $this->syncParallelGroupDisplayOrder($stage);
        });
    }

    /**
     * Stages in the same parallel group are activated together by the workflow
     * engine only when they share an identical display_order (see
     * WorkflowEngineService::activateNextStages()). A mismatch there either
     * silently drops a branch's approvers or permanently stalls the instance,
     * so every sibling is force-kept in sync whenever one member is saved.
     *
     * @param WorkflowStage $stage
     */
    private function syncParallelGroupDisplayOrder(WorkflowStage $stage): void
    {
        if ($stage->parallel_group_id === null) {
            return;
        }

        WorkflowStage::where('parallel_group_id', $stage->parallel_group_id)
            ->where('id', '!=', $stage->id)
            ->update(['display_order' => $stage->display_order]);
    }
}
