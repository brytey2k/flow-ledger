<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\WorkflowStageStoreRequest;
use App\Http\Requests\Tenant\WorkflowStageUpdateRequest;
use App\Models\Role;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowStageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkflowStagesController extends Controller
{
    public function __construct(
        private readonly WorkflowStageService $service,
    ) {}

    public function create(WorkflowTemplate $workflowTemplate): View
    {
        $roles = Role::orderBy('name')->get();
        $parallelGroups = $workflowTemplate->parallelGroups()->orderBy('name')->get();

        return view('tenant.workflow-stages.create', compact('workflowTemplate', 'roles', 'parallelGroups'));
    }

    public function store(WorkflowStageStoreRequest $request, WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        $data = $request->validated();
        $rawRoleIds = $data['role_ids'] ?? [];
        /** @var array<int, int> $roleIds */
        $roleIds = is_array($rawRoleIds) ? array_map(fn(mixed $v) => is_numeric($v) ? (int) $v : 0, $rawRoleIds) : [];
        unset($data['role_ids']);

        $this->service->create($workflowTemplate, $data, $roleIds);

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', 'Stage added.');
    }

    public function edit(WorkflowTemplate $workflowTemplate, WorkflowStage $workflowStage): View
    {
        $roles = Role::orderBy('name')->get();
        $parallelGroups = $workflowTemplate->parallelGroups()->orderBy('name')->get();
        $workflowStage->load('roles');

        return view('tenant.workflow-stages.edit', compact('workflowTemplate', 'workflowStage', 'roles', 'parallelGroups'));
    }

    public function update(WorkflowStageUpdateRequest $request, WorkflowTemplate $workflowTemplate, WorkflowStage $workflowStage): RedirectResponse
    {
        $data = $request->validated();
        $rawRoleIds = $data['role_ids'] ?? [];
        /** @var array<int, int> $roleIds */
        $roleIds = is_array($rawRoleIds) ? array_map(fn(mixed $v) => is_numeric($v) ? (int) $v : 0, $rawRoleIds) : [];
        unset($data['role_ids']);

        $this->service->update($workflowStage, $data, $roleIds);

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', 'Stage updated.');
    }

    public function destroy(WorkflowTemplate $workflowTemplate, WorkflowStage $workflowStage): RedirectResponse
    {
        $workflowStage->delete();

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', 'Stage deleted.');
    }
}
