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
        });
    }
}
