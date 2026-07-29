<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\WorkflowTemplateStatus;
use App\Models\Role;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowTemplateVersioningService;

function makeServiceForWorkflowTemplateVersioningService(): WorkflowTemplateVersioningService
{
    return app(WorkflowTemplateVersioningService::class);
}
test('should fork returns true when structural and has active instances', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    markTemplateActive($template);

    expect(makeServiceForWorkflowTemplateVersioningService()->shouldFork($template, isStructuralChange: true))->toBeTrue();
});
test('should fork returns false when structural but no active instances', function () {
    $template = WorkflowTemplate::factory()->advance()->create();

    expect(makeServiceForWorkflowTemplateVersioningService()->shouldFork($template, isStructuralChange: true))->toBeFalse();
});
test('should fork returns false when not structural regardless of active instances', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    markTemplateActive($template);

    expect(makeServiceForWorkflowTemplateVersioningService()->shouldFork($template, isStructuralChange: false))->toBeFalse();
});
test('draft for returns null when no draft exists', function () {
    $template = WorkflowTemplate::factory()->advance()->create();

    expect(makeServiceForWorkflowTemplateVersioningService()->draftFor($template))->toBeNull();
});
test('draft for returns itself when called on a draft', function () {
    $draft = WorkflowTemplate::factory()->advance()->draft()->create();

    expect(makeServiceForWorkflowTemplateVersioningService()->draftFor($draft)->id)->toBe($draft->id);
});
test('draft for finds existing draft from the published sibling', function () {
    $current = WorkflowTemplate::factory()->advance()->create();
    $draft = WorkflowTemplate::factory()->advance()->draft()->create([
        'template_group_id' => $current->template_group_id,
        'version' => 2,
    ]);

    $found = makeServiceForWorkflowTemplateVersioningService()->draftFor($current);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($draft->id);
});
test('fork draft creates new row with incremented version and same group id', function () {
    $template = WorkflowTemplate::factory()->advance()->create();

    $fork = makeServiceForWorkflowTemplateVersioningService()->forkDraft($template);

    $this->assertNotSame($template->id, $fork->newTemplate->id);
    expect($fork->newTemplate->version)->toBe(2);
    expect($fork->newTemplate->template_group_id)->toBe($template->template_group_id);
    expect($fork->newTemplate->is_current)->toBeFalse();
    expect($fork->newTemplate->status)->toBe(WorkflowTemplateStatus::Draft->value);
});
test('fork draft leaves old row is current untouched', function () {
    $template = WorkflowTemplate::factory()->advance()->create();

    makeServiceForWorkflowTemplateVersioningService()->forkDraft($template);

    expect($template->fresh()->is_current)->toBeTrue();
});
test('fork draft preserves type and branch id from source', function () {
    $branch = App\Models\Tenant\Branch::factory()->create();
    $template = WorkflowTemplate::factory()->retirement()->create(['branch_id' => $branch->id]);

    $fork = makeServiceForWorkflowTemplateVersioningService()->forkDraft($template);

    expect($fork->newTemplate->type)->toBe('retirement');
    expect($fork->newTemplate->branch_id)->toBe($branch->id);
});
test('fork draft clones all stages with correct attributes', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $role = Role::create(['name' => 'fork_role', 'guard_name' => 'web']);
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'name' => 'Finance Review',
        'display_order' => 2,
        'skip_below_amount' => 250.00,
    ]);
    $stage->roles()->sync([$role->id]);

    $fork = makeServiceForWorkflowTemplateVersioningService()->forkDraft($template);

    $clonedStage = $fork->newTemplate->stages()->firstOrFail();
    expect($clonedStage->name)->toBe('Finance Review');
    expect($clonedStage->display_order)->toBe(2);
    expect((float) $clonedStage->skip_below_amount)->toEqualWithDelta(250.00, 0.01);
    $this->assertNotSame($stage->id, $clonedStage->id);
});
test('fork draft clones all parallel groups', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    WorkflowParallelGroup::factory()->create([
        'workflow_template_id' => $template->id,
        'name' => 'Finance & HR',
        'require_all' => true,
    ]);

    $fork = makeServiceForWorkflowTemplateVersioningService()->forkDraft($template);

    $clonedGroup = $fork->newTemplate->parallelGroups()->firstOrFail();
    expect($clonedGroup->name)->toBe('Finance & HR');
    expect($clonedGroup->require_all)->toBeTrue();
});
test('fork draft remaps parallel group id on cloned stages correctly', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
    WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'parallel_group_id' => $group->id,
    ]);

    $fork = makeServiceForWorkflowTemplateVersioningService()->forkDraft($template);

    $clonedStage = $fork->newTemplate->stages()->firstOrFail();
    $clonedGroup = $fork->newTemplate->parallelGroups()->firstOrFail();
    expect($clonedStage->parallel_group_id)->toBe($clonedGroup->id);
    $this->assertNotSame($group->id, $clonedStage->parallel_group_id);
});
test('fork draft clones stage role pivots', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $role = Role::create(['name' => 'fork_role_pivot', 'guard_name' => 'web']);
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $stage->roles()->sync([$role->id]);

    $fork = makeServiceForWorkflowTemplateVersioningService()->forkDraft($template);

    $clonedStage = $fork->newTemplate->stages()->firstOrFail();
    expect($clonedStage->roles->pluck('id')->contains($role->id))->toBeTrue();
});
test('fork draft returns stage id map covering every original stage', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stageOne = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $stageTwo = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

    $fork = makeServiceForWorkflowTemplateVersioningService()->forkDraft($template);

    expect($fork->stageIdMap)->toHaveKey($stageOne->id);
    expect($fork->stageIdMap)->toHaveKey($stageTwo->id);
    expect($fork->stageIdMap)->toHaveCount(2);
});
test('publish marks draft current and published', function () {
    $current = WorkflowTemplate::factory()->advance()->create();
    $draft = WorkflowTemplate::factory()->advance()->draft()->create([
        'template_group_id' => $current->template_group_id,
        'version' => 2,
    ]);

    makeServiceForWorkflowTemplateVersioningService()->publish($draft);

    $draft->refresh();
    expect($draft->is_current)->toBeTrue();
    expect($draft->status)->toBe(WorkflowTemplateStatus::Published->value);
});
test('publish flips previous current sibling to not current', function () {
    $current = WorkflowTemplate::factory()->advance()->create();
    $draft = WorkflowTemplate::factory()->advance()->draft()->create([
        'template_group_id' => $current->template_group_id,
        'version' => 2,
    ]);

    makeServiceForWorkflowTemplateVersioningService()->publish($draft);

    expect($current->fresh()->is_current)->toBeFalse();
});
test('publish throws when template is not a draft', function () {
    $template = WorkflowTemplate::factory()->advance()->create();

    $this->expectException(InvalidArgumentException::class);

    makeServiceForWorkflowTemplateVersioningService()->publish($template);
});
test('discard deletes the draft row', function () {
    $draft = WorkflowTemplate::factory()->advance()->draft()->create();

    makeServiceForWorkflowTemplateVersioningService()->discard($draft);

    $this->assertDatabaseMissing('workflow_templates', ['id' => $draft->id]);
});
test('discard cascades cloned stages and groups', function () {
    $current = WorkflowTemplate::factory()->advance()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $current->id]);
    $fork = makeServiceForWorkflowTemplateVersioningService()->forkDraft($current);

    makeServiceForWorkflowTemplateVersioningService()->discard($fork->newTemplate);

    $this->assertDatabaseMissing('workflow_stages', ['workflow_template_id' => $fork->newTemplate->id]);
});
test('discard throws when template is not a draft', function () {
    $template = WorkflowTemplate::factory()->advance()->create();

    $this->expectException(InvalidArgumentException::class);

    makeServiceForWorkflowTemplateVersioningService()->discard($template);
});
function markTemplateActive(WorkflowTemplate $template): void
{
    $subject = PaymentRequest::factory()->inWorkflow()->create();
    WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $subject->id,
        'status' => 'in_progress',
    ]);
}
