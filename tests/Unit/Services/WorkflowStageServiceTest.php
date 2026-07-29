<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\DTOs\Tenant\WorkflowStageDto;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowStageService;

function makeServiceForWorkflowStageService(): WorkflowStageService
{
    return app(WorkflowStageService::class);
}
function makeTemplate(): WorkflowTemplate
{
    return WorkflowTemplate::factory()->advance()->create();
}
test('create returns workflow stage instance', function () {
    $template = makeTemplate();
    $dto = new WorkflowStageDto(
        name: 'Finance Review',
        displayOrder: 1,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
    );

    $stage = makeServiceForWorkflowStageService()->create($template, $dto);

    expect($stage)->toBeInstanceOf(WorkflowStage::class);
});
test('create persists stage with correct name', function () {
    $template = makeTemplate();
    $dto = new WorkflowStageDto(
        name: 'Manager Approval',
        displayOrder: 1,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
    );

    $stage = makeServiceForWorkflowStageService()->create($template, $dto);

    expect(WorkflowStage::findOrFail($stage->id)->name)->toBe('Manager Approval');
});
test('create persists stage with correct display order', function () {
    $template = makeTemplate();
    $dto = new WorkflowStageDto(
        name: 'Review Stage',
        displayOrder: 3,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
    );

    $stage = makeServiceForWorkflowStageService()->create($template, $dto);

    expect($stage->display_order)->toBe(3);
});
test('create associates stage with template', function () {
    $template = makeTemplate();
    $dto = new WorkflowStageDto(
        name: 'Approval',
        displayOrder: 1,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
    );

    $stage = makeServiceForWorkflowStageService()->create($template, $dto);

    expect($stage->workflow_template_id)->toBe($template->id);
});
test('create syncs roles to stage', function () {
    $template = makeTemplate();
    $dto = new WorkflowStageDto(
        name: 'Review',
        displayOrder: 1,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [$this->role->id],
    );

    $stage = makeServiceForWorkflowStageService()->create($template, $dto);

    expect($stage->roles->pluck('id')->contains($this->role->id))->toBeTrue();
});
test('create syncs multiple roles to stage', function () {
    $template = makeTemplate();
    $extraRole = App\Models\Role::create(['name' => 'extra_role_' . Illuminate\Support\Str::uuid(), 'guard_name' => 'web']);
    $dto = new WorkflowStageDto(
        name: 'Review',
        displayOrder: 1,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [$this->role->id, $extraRole->id],
    );

    $stage = makeServiceForWorkflowStageService()->create($template, $dto);

    $roleIds = $stage->roles->pluck('id');
    expect($roleIds->contains($this->role->id))->toBeTrue();
    expect($roleIds->contains($extraRole->id))->toBeTrue();
});
test('create sets skip below amount', function () {
    $template = makeTemplate();
    $dto = new WorkflowStageDto(
        name: 'Threshold Stage',
        displayOrder: 1,
        skipBelowAmount: 500.00,
        parallelGroupId: null,
        roleIds: [],
    );

    $stage = makeServiceForWorkflowStageService()->create($template, $dto);

    expect((float) $stage->skip_below_amount)->toEqualWithDelta(500.00, 0.01);
});
test('create sets scope to department', function () {
    $template = makeTemplate();
    $dto = new WorkflowStageDto(
        name: 'Dept Scoped',
        displayOrder: 1,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
        scopeToDepartment: true,
    );

    $stage = makeServiceForWorkflowStageService()->create($template, $dto);

    expect($stage->scope_to_department)->toBeTrue();
});
test('create sets scope to branch', function () {
    $template = makeTemplate();
    $dto = new WorkflowStageDto(
        name: 'Branch Scoped',
        displayOrder: 1,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
        scopeToBranch: true,
    );

    $stage = makeServiceForWorkflowStageService()->create($template, $dto);

    expect($stage->scope_to_branch)->toBeTrue();
});
test('update changes stage name', function () {
    $template = makeTemplate();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $dto = new WorkflowStageDto(
        name: 'Updated Stage Name',
        displayOrder: $stage->display_order,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    expect($stage->fresh()->name)->toBe('Updated Stage Name');
});
test('update changes display order', function () {
    $template = makeTemplate();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $dto = new WorkflowStageDto(
        name: $stage->name,
        displayOrder: 5,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    expect($stage->fresh()->display_order)->toBe(5);
});
test('update syncs roles replacing old ones', function () {
    $template = makeTemplate();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $stage->roles()->sync([$this->role->id]);

    $newRole = App\Models\Role::create(['name' => 'new_role_' . Illuminate\Support\Str::uuid(), 'guard_name' => 'web']);
    $dto = new WorkflowStageDto(
        name: $stage->name,
        displayOrder: $stage->display_order,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [$newRole->id],
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    $roleIds = $stage->fresh()->roles->pluck('id');
    expect($roleIds->contains($newRole->id))->toBeTrue();
    expect($roleIds->contains($this->role->id))->toBeFalse();
});
test('update syncs role from tenant test case', function () {
    $template = makeTemplate();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $dto = new WorkflowStageDto(
        name: $stage->name,
        displayOrder: $stage->display_order,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [$this->role->id],
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    expect($stage->fresh()->roles->pluck('id')->contains($this->role->id))->toBeTrue();
});
test('update sets skip below amount', function () {
    $template = makeTemplate();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $dto = new WorkflowStageDto(
        name: $stage->name,
        displayOrder: $stage->display_order,
        skipBelowAmount: 1000.00,
        parallelGroupId: null,
        roleIds: [],
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    expect((float) $stage->fresh()->skip_below_amount)->toEqualWithDelta(1000.00, 0.01);
});
test('update clears skip below amount when null', function () {
    $template = makeTemplate();
    $stage = WorkflowStage::factory()->withThreshold(500.0)->create(['workflow_template_id' => $template->id]);
    $dto = new WorkflowStageDto(
        name: $stage->name,
        displayOrder: $stage->display_order,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    expect($stage->fresh()->skip_below_amount)->toBeNull();
});
test('update sets scope to department', function () {
    $template = makeTemplate();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $dto = new WorkflowStageDto(
        name: $stage->name,
        displayOrder: $stage->display_order,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
        scopeToDepartment: true,
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    expect($stage->fresh()->scope_to_department)->toBeTrue();
});
test('update sets scope to branch', function () {
    $template = makeTemplate();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $dto = new WorkflowStageDto(
        name: $stage->name,
        displayOrder: $stage->display_order,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
        scopeToBranch: true,
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    expect($stage->fresh()->scope_to_branch)->toBeTrue();
});
test('create syncs display order to existing group siblings', function () {
    $template = makeTemplate();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
    $existing = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'parallel_group_id' => $group->id,
        'display_order' => 1,
    ]);

    $dto = new WorkflowStageDto(
        name: 'Second Approver',
        displayOrder: 5,
        skipBelowAmount: null,
        parallelGroupId: $group->id,
        roleIds: [],
    );

    makeServiceForWorkflowStageService()->create($template, $dto);

    expect($existing->fresh()->display_order)->toBe(5);
});
test('create does not touch stages outside the group', function () {
    $template = makeTemplate();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
    $unrelated = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'parallel_group_id' => null,
        'display_order' => 1,
    ]);

    $dto = new WorkflowStageDto(
        name: 'Grouped Stage',
        displayOrder: 7,
        skipBelowAmount: null,
        parallelGroupId: $group->id,
        roleIds: [],
    );

    makeServiceForWorkflowStageService()->create($template, $dto);

    expect($unrelated->fresh()->display_order)->toBe(1);
});
test('update syncs display order to all other group members', function () {
    $template = makeTemplate();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'parallel_group_id' => $group->id,
        'display_order' => 2,
    ]);
    $sibling1 = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'parallel_group_id' => $group->id,
        'display_order' => 2,
    ]);
    $sibling2 = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'parallel_group_id' => $group->id,
        'display_order' => 2,
    ]);

    $dto = new WorkflowStageDto(
        name: $stage->name,
        displayOrder: 9,
        skipBelowAmount: null,
        parallelGroupId: $group->id,
        roleIds: [],
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    expect($sibling1->fresh()->display_order)->toBe(9);
    expect($sibling2->fresh()->display_order)->toBe(9);
});
test('update moving stage out of group leaves former siblings untouched', function () {
    $template = makeTemplate();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'parallel_group_id' => $group->id,
        'display_order' => 3,
    ]);
    $sibling = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'parallel_group_id' => $group->id,
        'display_order' => 3,
    ]);

    $dto = new WorkflowStageDto(
        name: $stage->name,
        displayOrder: 8,
        skipBelowAmount: null,
        parallelGroupId: null,
        roleIds: [],
    );

    makeServiceForWorkflowStageService()->update($stage, $dto);

    expect($sibling->fresh()->display_order)->toBe(3);
});
