<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\WorkflowTemplateStoreRequest;
use App\Http\Requests\Tenant\WorkflowTemplateUpdateRequest;
use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\BranchRepository;
use App\Repositories\WorkflowTemplateRepository;
use App\Services\WorkflowTemplateVersioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class WorkflowTemplatesController extends Controller
{
    public function __construct(
        private readonly WorkflowTemplateRepository $repository,
        private readonly BranchRepository $branches,
        private readonly WorkflowTemplateVersioningService $versioning,
    ) {}

    public function versions(WorkflowTemplate $workflowTemplate): View
    {
        $versions = $this->repository->allVersionsForFamily($workflowTemplate->template_group_id);

        return view('tenant.workflow-templates.versions', compact('workflowTemplate', 'versions'));
    }

    public function index(): View
    {
        $templates = $this->repository->allWithStageCount();

        return view('tenant.workflow-templates.index', compact('templates'));
    }

    public function create(): View
    {
        $branches = $this->branches->allOrderedByName();

        return view('tenant.workflow-templates.create', compact('branches'));
    }

    public function store(WorkflowTemplateStoreRequest $request): RedirectResponse
    {
        $dto = $request->toDto();
        $template = WorkflowTemplate::create([
            'name' => $dto->name,
            'type' => $dto->type,
            'branch_id' => $dto->branchId,
        ]);

        return redirect()->route('workflow-templates.show', $template)
            ->with('success', __('flash.workflows.template_created'));
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
        // Name is cosmetic: always edited in place, regardless of active instances, and never versioned.
        $dto = $request->toDto();
        $workflowTemplate->update(['name' => $dto->name]);

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.template_updated'));
    }

    public function publish(WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        abort_unless($workflowTemplate->isDraft(), 422, 'Only a draft version can be published.');

        $this->versioning->publish($workflowTemplate);

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.template_published'));
    }

    public function discardDraft(WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        abort_unless($workflowTemplate->isDraft(), 422, 'Only a draft version can be discarded.');

        $published = WorkflowTemplate::where('template_group_id', $workflowTemplate->template_group_id)
            ->where('is_current', true)
            ->firstOrFail();

        $this->versioning->discard($workflowTemplate);

        return redirect()->route('workflow-templates.show', $published)
            ->with('success', __('flash.workflows.draft_discarded'));
    }

    public function destroy(WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        $family = WorkflowTemplate::where('template_group_id', $workflowTemplate->template_group_id)->get();

        if ($family->contains(fn(WorkflowTemplate $version): bool => $version->hasActiveInstances())) {
            return redirect()->route('workflow-templates.show', $workflowTemplate)
                ->with('error', __('flash.workflows.template_locked'));
        }

        if ($family->contains(fn(WorkflowTemplate $version): bool => $version->instances()->exists())) {
            return redirect()->route('workflow-templates.show', $workflowTemplate)
                ->with('error', __('flash.workflows.template_has_history'));
        }

        foreach ($family as $version) {
            $version->delete();
        }

        return redirect()->route('workflow-templates.index')
            ->with('success', __('flash.workflows.template_deleted'));
    }
}
