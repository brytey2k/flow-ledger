<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\WorkflowParallelGroupStoreRequest;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Http\RedirectResponse;

class WorkflowParallelGroupsController extends Controller
{
    public function store(WorkflowParallelGroupStoreRequest $request, WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        $dto = $request->toDto();
        $workflowTemplate->parallelGroups()->create([
            'name' => $dto->name,
            'require_all' => $dto->requireAll,
        ]);

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', 'Parallel group created.');
    }

    public function destroy(WorkflowTemplate $workflowTemplate, WorkflowParallelGroup $workflowParallelGroup): RedirectResponse
    {
        $workflowParallelGroup->delete();

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', 'Parallel group deleted.');
    }
}
