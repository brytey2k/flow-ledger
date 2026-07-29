<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\DTOs\Tenant\WorkflowStageDto;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\WorkflowStageStoreRequest;
use App\Http\Requests\Tenant\WorkflowStageUpdateRequest;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\RoleRepository;
use App\Services\WorkflowStageService;
use App\Services\WorkflowTemplateVersioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class WorkflowStagesController extends Controller
{
    public function __construct(
        private readonly WorkflowStageService $service,
        private readonly RoleRepository $roleRepository,
        private readonly WorkflowTemplateVersioningService $versioning,
    ) {}

    public function create(WorkflowTemplate $workflowTemplate): View
    {
        $roles = $this->roleRepository->allOrderedByName();
        $parallelGroups = $workflowTemplate->parallelGroups()->with('stages')->orderBy('name')->get();
        $parallelGroupStages = $this->parallelGroupStagesForJs($parallelGroups);

        return view('tenant.workflow-stages.create', compact('workflowTemplate', 'roles', 'parallelGroups', 'parallelGroupStages'));
    }

    public function store(WorkflowStageStoreRequest $request, WorkflowTemplate $workflowTemplate): RedirectResponse
    {
        $dto = $request->toDto();

        if ($this->versioning->shouldFork($workflowTemplate, isStructuralChange: true)) {
            if (($draft = $this->versioning->draftFor($workflowTemplate)) !== null) {
                return redirect()->route('workflow-templates.show', $draft)
                    ->with('warning', __('flash.workflows.draft_in_progress'));
            }

            $fork = $this->versioning->forkDraft($workflowTemplate);
            $workflowTemplate = $fork->newTemplate;
            $dto = $this->remapParallelGroupId($dto, $fork->groupIdMap);
        }

        $this->service->create($workflowTemplate, $dto);

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.stage_added'));
    }

    public function edit(WorkflowTemplate $workflowTemplate, WorkflowStage $workflowStage): View
    {
        $roles = $this->roleRepository->allOrderedByName();
        $parallelGroups = $workflowTemplate->parallelGroups()->with('stages')->orderBy('name')->get();
        $parallelGroupStages = $this->parallelGroupStagesForJs($parallelGroups);
        $workflowStage->load('roles');

        return view('tenant.workflow-stages.edit', compact('workflowTemplate', 'workflowStage', 'roles', 'parallelGroups', 'parallelGroupStages'));
    }

    public function update(WorkflowStageUpdateRequest $request, WorkflowTemplate $workflowTemplate, WorkflowStage $workflowStage): RedirectResponse
    {
        $dto = $request->toDto();

        if ($this->versioning->shouldFork($workflowTemplate, isStructuralChange: true)) {
            if (($draft = $this->versioning->draftFor($workflowTemplate)) !== null) {
                return redirect()->route('workflow-templates.show', $draft)
                    ->with('warning', __('flash.workflows.draft_in_progress'));
            }

            $fork = $this->versioning->forkDraft($workflowTemplate);
            $workflowTemplate = $fork->newTemplate;
            $workflowStage = WorkflowStage::findOrFail($fork->stageIdMap[$workflowStage->id]);
            $dto = $this->remapParallelGroupId($dto, $fork->groupIdMap);
        }

        $this->service->update($workflowStage, $dto);

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.stage_updated'));
    }

    public function destroy(WorkflowTemplate $workflowTemplate, WorkflowStage $workflowStage): RedirectResponse
    {
        if ($this->versioning->shouldFork($workflowTemplate, isStructuralChange: true)) {
            if (($draft = $this->versioning->draftFor($workflowTemplate)) !== null) {
                return redirect()->route('workflow-templates.show', $draft)
                    ->with('warning', __('flash.workflows.draft_in_progress'));
            }

            $fork = $this->versioning->forkDraft($workflowTemplate);
            $workflowTemplate = $fork->newTemplate;
            $workflowStage = WorkflowStage::findOrFail($fork->stageIdMap[$workflowStage->id]);
        }

        $workflowStage->delete();

        return redirect()->route('workflow-templates.show', $workflowTemplate)
            ->with('success', __('flash.workflows.stage_deleted'));
    }

    /** @param array<int, int> $groupIdMap */
    private function remapParallelGroupId(WorkflowStageDto $dto, array $groupIdMap): WorkflowStageDto
    {
        if ($dto->parallelGroupId === null) {
            return $dto;
        }

        return new WorkflowStageDto(
            name: $dto->name,
            displayOrder: $dto->displayOrder,
            skipBelowAmount: $dto->skipBelowAmount,
            parallelGroupId: $groupIdMap[$dto->parallelGroupId] ?? null,
            roleIds: $dto->roleIds,
            scopeToDepartment: $dto->scopeToDepartment,
            scopeToBranch: $dto->scopeToBranch,
        );
    }

    /**
     * Lets the stage form warn client-side, before submit, when the chosen
     * display order would desync from the other members of a parallel group.
     *
     * @param Collection<int, WorkflowParallelGroup> $parallelGroups
     *
     * @return array<int, array<int, array{id: int, name: string, display_order: int}>>
     */
    private function parallelGroupStagesForJs(Collection $parallelGroups): array
    {
        return $parallelGroups
            ->mapWithKeys(fn(WorkflowParallelGroup $group): array => [
                $group->id => $group->stages
                    ->map(fn(WorkflowStage $stage): array => [
                        'id' => $stage->id,
                        'name' => $stage->name,
                        'display_order' => $stage->display_order,
                    ])
                    ->values()
                    ->all(),
            ])
            ->all();
    }
}
