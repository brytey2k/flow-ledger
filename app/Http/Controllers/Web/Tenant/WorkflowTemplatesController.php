<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\WorkflowTemplateStoreRequest;
use App\Http\Requests\Tenant\WorkflowTemplateUpdateRequest;
use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\WorkflowTemplateRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkflowTemplatesController extends Controller
{
    public function __construct(
        private readonly WorkflowTemplateRepository $repository,
    ) {}

    public function index(): View
    {
        $templates = $this->repository->allWithStageCount();

        return view('tenant.workflow-templates.index', compact('templates'));
    }

    public function create(): View
    {
        return view('tenant.workflow-templates.create');
    }

    public function store(WorkflowTemplateStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();
        $template = WorkflowTemplate::create([
            'name' => $dto->name,
            'type' => $dto->type,
        ]);

        return redirect()->route('workflow-templates.show', $template)
            ->with('success', 'Workflow template created. Now add stages.');
    }

    public function show(WorkflowTemplate $workflowTemplate): View
    {
        $workflowTemplate->load(['stages.roles', 'stages.parallelGroup', 'parallelGroups']);

        return view('tenant.workflow-templates.show', compact('workflowTemplate'));
    }

    public function edit(WorkflowTemplate $workflowTemplate): View
    {
        return view('tenant.workflow-templates.edit', compact('workflowTemplate'));
    }

    public function update(WorkflowTemplateUpdateRequest $request, WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        $dto = $request->toDto();
        $workflowTemplate->update([
            'name' => $dto->name,
            'type' => $dto->type,
        ]);

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', 'Workflow template updated.');
    }

    public function destroy(WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        $workflowTemplate->delete();

        return redirect()->route('workflow-templates.index')
            ->with('success', 'Workflow template deleted.');
    }
}
