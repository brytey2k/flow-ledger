<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class WorkflowTemplateRepository
{
    /** @return Collection<int, WorkflowTemplate> */
    public function allWithStageCount(): Collection
    {
        return WorkflowTemplate::withCount('stages')->with('branch')->where('is_current', true)->orderBy('name')->get();
    }

    /** @return Collection<int, WorkflowTemplate> */
    public function allVersionsForFamily(string $templateGroupId): Collection
    {
        return WorkflowTemplate::where('template_group_id', $templateGroupId)
            ->withCount([
                'instances',
                /* @param Builder<WorkflowInstance> $query */
                'instances as active_instances_count' => fn(Builder $query): Builder => $query->where('status', 'in_progress'),
            ])
            ->orderByDesc('version')
            ->get();
    }
}
