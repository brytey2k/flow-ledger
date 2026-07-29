<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\WorkflowParallelGroupStoreRequest;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowTemplateVersioningService;
use Illuminate\Http\RedirectResponse;

class WorkflowParallelGroupsController extends Controller
{
    public function __construct(
        private readonly WorkflowTemplateVersioningService $versioning,
    ) {}

    public function store(WorkflowParallelGroupStoreRequest $request, WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        if ($this->versioning->shouldFork($workflowTemplate, isStructuralChange: true)) {
            if (($draft = $this->versioning->draftFor($workflowTemplate)) !== null) {
                return redirect()->route('workflow-templates.show', $draft)
                    ->with('warning', __('flash.workflows.draft_in_progress'));
            }

            $workflowTemplate = $this->versioning->forkDraft($workflowTemplate)->newTemplate;
        }

        $dto = $request->toDto();
        $workflowTemplate->parallelGroups()->create([
            'name' => $dto->name,
            'require_all' => $dto->requireAll,
        ]);

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.parallel_group_created'));
    }

    public function destroy(WorkflowTemplate $workflowTemplate, WorkflowParallelGroup $workflowParallelGroup): RedirectResponse
    {
        if ($this->versioning->shouldFork($workflowTemplate, isStructuralChange: true)) {
            if (($draft = $this->versioning->draftFor($workflowTemplate)) !== null) {
                return redirect()->route('workflow-templates.show', $draft)
                    ->with('warning', __('flash.workflows.draft_in_progress'));
            }

            $fork = $this->versioning->forkDraft($workflowTemplate);
            $workflowTemplate = $fork->newTemplate;
            $workflowParallelGroup = WorkflowParallelGroup::findOrFail($fork->groupIdMap[$workflowParallelGroup->id]);
        }

        $workflowParallelGroup->delete();

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.parallel_group_deleted'));
    }
}
