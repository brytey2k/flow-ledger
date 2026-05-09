<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Support\Facades\DB;

class WorkflowStageService
{
    /**
     * @param WorkflowTemplate $template
     * @param array<string, mixed> $data
     * @param array<int, int> $roleIds
     */
    public function create(WorkflowTemplate $template, array $data, array $roleIds): WorkflowStage
    {
        return DB::transaction(function () use ($template, $data, $roleIds): WorkflowStage {
            /** @var WorkflowStage $stage */
            $stage = $template->stages()->create($data);
            $stage->roles()->sync($roleIds);

            return $stage;
        });
    }

    /**
     * @param WorkflowStage $stage
     * @param array<string, mixed> $data
     * @param array<int, int> $roleIds
     */
    public function update(WorkflowStage $stage, array $data, array $roleIds): void
    {
        DB::transaction(function () use ($stage, $data, $roleIds): void {
            $stage->update($data);
            $stage->roles()->sync($roleIds);
        });
    }
}
