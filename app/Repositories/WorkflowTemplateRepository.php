<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Database\Eloquent\Collection;

class WorkflowTemplateRepository
{
    /** @return Collection<int, WorkflowTemplate> */
    public function allWithStageCount(): Collection
    {
        return WorkflowTemplate::withCount('stages')->orderBy('name')->get();
    }
}
