<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\WorkflowStageStoreRequest;
use App\Http\Requests\Tenant\WorkflowStageUpdateRequest;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\RoleRepository;
use App\Services\WorkflowStageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkflowStagesController extends Controller
{
    public function __construct(
        private readonly WorkflowStageService $service,
        private readonly RoleRepository $roleRepository,
    ) {}

    public function create(WorkflowTemplate $workflowTemplate): View
    {
        $roles = $this->roleRepository->allOrderedByName();
        $parallelGroups = $workflowTemplate->parallelGroups()->orderBy('name')->get();

        return view('tenant.workflow-stages.create', compact('workflowTemplate', 'roles', 'parallelGroups'));
    }

    public function store(WorkflowStageStoreRequest $request, WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        if ($workflowTemplate->hasActiveInstances()) {
            return redirect()->route('workflow-templates.show', $workflowTemplate)
                ->with('error', __('flash.workflows.template_locked'));
        }

        $this->service->create($workflowTemplate, $request->toDto());

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.stage_added'));
    }

    public function edit(WorkflowTemplate $workflowTemplate, WorkflowStage $workflowStage): View
    {
        $roles = $this->roleRepository->allOrderedByName();
        $parallelGroups = $workflowTemplate->parallelGroups()->orderBy('name')->get();
        $workflowStage->load('roles');

        return view('tenant.workflow-stages.edit', compact('workflowTemplate', 'workflowStage', 'roles', 'parallelGroups'));
    }

    public function update(WorkflowStageUpdateRequest $request, WorkflowTemplate $workflowTemplate, WorkflowStage $workflowStage): RedirectResponse
    {
        if ($workflowTemplate->hasActiveInstances()) {
            return redirect()->route('workflow-templates.show', $workflowTemplate)
                ->with('error', __('flash.workflows.template_locked'));
        }

        $this->service->update($workflowStage, $request->toDto());

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.stage_updated'));
    }

    public function destroy(WorkflowTemplate $workflowTemplate, WorkflowStage $workflowStage): RedirectResponse
    {
        if ($workflowTemplate->hasActiveInstances()) {
            return redirect()->route('workflow-templates.show', $workflowTemplate)
                ->with('error', __('flash.workflows.template_locked'));
        }

        $workflowStage->delete();

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.stage_deleted'));
    }
}
