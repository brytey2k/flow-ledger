<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Role;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Department;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowEngineService;

beforeEach(function () {
    $this->engine = app(WorkflowEngineService::class);
});
// ── Helpers ───────────────────────────────────────────────────────────────
/**
 * Build a PaymentRequest with all required FK relations resolved.
 *
 * @param array<string, mixed> $overrides
 */
function makePaymentRequestForWorkflowEngineService(array $overrides = []): PaymentRequest
{
    $currency = Currency::factory()->create();

    return PaymentRequest::factory()->inWorkflow()->create(array_merge([
        'branch_id' => test()->branch->id,
        'currency_id' => $currency->id,
    ], $overrides));
}
/**
 * Build a sequential template with N stages at consecutive display_order values.
 *
 * @param int $stageCount
 *
 * @return array{template: WorkflowTemplate, stages: WorkflowStage[], role: Role}
 */
function makeSequentialTemplate(int $stageCount = 2): array
{
    $template = WorkflowTemplate::factory()->advance()->create();
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    $stages = [];
    for ($i = 1; $i <= $stageCount; $i++) {
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => $i,
        ]);
        $stage->roles()->sync([$role->id]);
        $stages[] = $stage->fresh();
    }

    $template->load('stages');

    return ['template' => $template, 'stages' => $stages, 'role' => $role];
}
test('start workflow creates instance and pending stages', function () {
    ['template' => $template, 'stages' => $stages] = makeSequentialTemplate(2);
    $subject = makePaymentRequestForWorkflowEngineService();

    $instance = $this->engine->startWorkflow($subject, $template);

    $this->assertDatabaseHas('workflow_instances', [
        'id' => $instance->id,
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $subject->id,
        'status' => 'in_progress',
    ]);

    $this->assertDatabaseCount('workflow_instance_stages', 2);
});
test('start workflow activates first stage only', function () {
    ['template' => $template] = makeSequentialTemplate(3);
    $subject = makePaymentRequestForWorkflowEngineService();

    $this->engine->startWorkflow($subject, $template);

    $this->assertDatabaseHas('workflow_instance_stages', ['status' => 'active']);
    expect(WorkflowInstanceStage::where('status', 'active')->count())->toEqual(1);
    expect(WorkflowInstanceStage::where('status', 'pending')->count())->toEqual(2);
});
test('start workflow activates all stages in first parallel group', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $group = WorkflowParallelGroup::factory()->requireAll()->create(['workflow_template_id' => $template->id]);
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    // Two stages in the same parallel group at the same display_order
    foreach ([1, 2] as $n) {
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
            'display_order' => 1,
        ]);
        $stage->roles()->sync([$role->id]);
    }

    // A sequential stage after the group
    $later = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 2,
    ]);
    $later->roles()->sync([$role->id]);

    $template->load('stages');
    $subject = makePaymentRequestForWorkflowEngineService();

    $this->engine->startWorkflow($subject, $template);

    expect(WorkflowInstanceStage::where('status', 'active')->count())->toEqual(2);
    expect(WorkflowInstanceStage::where('status', 'pending')->count())->toEqual(1);
});
test('stage below threshold is skipped and next is activated', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    // Stage 1: skip if amount < 5000
    $s1 = WorkflowStage::factory()->withThreshold(5000)->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);
    $s1->roles()->sync([$role->id]);

    // Stage 2: no threshold
    $s2 = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 2,
    ]);
    $s2->roles()->sync([$role->id]);

    $template->load('stages');
    $subject = makePaymentRequestForWorkflowEngineService(['total_amount' => 1000.00]);

    $this->engine->startWorkflow($subject, $template);

    $instanceStage1 = WorkflowInstanceStage::where('workflow_stage_id', $s1->id)->firstOrFail();
    $instanceStage2 = WorkflowInstanceStage::where('workflow_stage_id', $s2->id)->firstOrFail();

    expect($instanceStage1->status)->toEqual('skipped');
    expect($instanceStage2->status)->toEqual('active');
});
test('stage meeting threshold is not skipped', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    $stage = WorkflowStage::factory()->withThreshold(500)->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);
    $stage->roles()->sync([$role->id]);

    $template->load('stages');
    $subject = makePaymentRequestForWorkflowEngineService(['total_amount' => 1000.00]);

    $this->engine->startWorkflow($subject, $template);

    $instanceStage = WorkflowInstanceStage::where('workflow_stage_id', $stage->id)->firstOrFail();
    expect($instanceStage->status)->toEqual('active');
});
test('approve first stage activates next stage', function () {
    ['template' => $template, 'stages' => $stages] = makeSequentialTemplate(2);
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
    $this->engine->approve($activeStage, $this->user, 'Looks good');

    $activeStage->refresh();
    expect($activeStage->status)->toEqual('approved');
    expect($activeStage->completed_at)->not->toBeNull();

    expect(WorkflowInstanceStage::where('status', 'active')->count())->toEqual(1);
    $this->assertDatabaseHas('workflow_actions', [
        'workflow_instance_stage_id' => $activeStage->id,
        'user_id' => $this->user->id,
        'action' => 'approve',
        'comment' => 'Looks good',
    ]);
});
test('approve last stage completes workflow and marks subject approved', function () {
    ['template' => $template] = makeSequentialTemplate(1);
    $subject = makePaymentRequestForWorkflowEngineService(['type' => App\Enums\Tenant\PaymentRequestType::Advance->value]);
    $instance = $this->engine->startWorkflow($subject, $template);

    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
    $this->engine->approve($activeStage, $this->user);

    $instance->refresh();
    expect($instance->status)->toEqual('completed');

    $subject->refresh();
    expect($subject->status)->toEqual('approved');
    expect($subject->approved_at)->not->toBeNull();
});
test('approve expense workflow completes and marks approved', function () {
    $template = WorkflowTemplate::factory()->expense()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
    $template->stages()->first()->roles()->sync([$role->id]);
    $this->user->roles()->sync([$role->id]);

    $subject = makePaymentRequestForWorkflowEngineService(['type' => App\Enums\Tenant\PaymentRequestType::Expense->value]);
    $instance = $this->engine->startWorkflow($subject, $template);

    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
    $this->engine->approve($activeStage, $this->user);

    $instance->refresh();
    expect($instance->status)->toEqual('completed');

    $subject->refresh();
    expect($subject->status)->toEqual('approved');
    expect($subject->approved_at)->not->toBeNull();
});
test('parallel and group waits for all siblings before advancing', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $group = WorkflowParallelGroup::factory()->requireAll()->create(['workflow_template_id' => $template->id]);
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s1->roles()->sync([$role->id]);
    $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s2->roles()->sync([$role->id]);

    $template->load('stages');
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    // Approve only the first sibling
    $firstActive = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
    $this->engine->approve($firstActive, $this->user);

    // Workflow should still be in progress — second sibling still active
    $instance->refresh();
    expect($instance->status)->toEqual('in_progress');
    expect(WorkflowInstanceStage::where('status', 'active')->count())->toEqual(1);

    // Now approve the second sibling
    $secondActive = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();
    $this->engine->approve($secondActive, $this->user);

    $instance->refresh();
    expect($instance->status)->toEqual('completed');
});
test('parallel or group cancels siblings on first approval', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $group = WorkflowParallelGroup::factory()->requireAny()->create(['workflow_template_id' => $template->id]);
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s1->roles()->sync([$role->id]);
    $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s2->roles()->sync([$role->id]);

    $template->load('stages');
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    $firstActive = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
    $this->engine->approve($firstActive, $this->user);

    // Sibling should be cancelled
    $sibling = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();
    expect($sibling->status)->toEqual('cancelled');

    // Workflow completes immediately (no more stages)
    $instance->refresh();
    expect($instance->status)->toEqual('completed');
});
test('reject cancels all remaining stages and marks subject cancelled', function () {
    ['template' => $template] = makeSequentialTemplate(3);
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
    $this->engine->reject($activeStage, $this->user, 'Not approved.');

    $activeStage->refresh();
    expect($activeStage->status)->toEqual('rejected');

    expect(WorkflowInstanceStage::whereIn('status', ['pending', 'active'])->count())->toEqual(0);

    $instance->refresh();
    expect($instance->status)->toEqual('cancelled');

    $subject->refresh();
    expect($subject->status)->toEqual('cancelled');

    $this->assertDatabaseHas('workflow_actions', [
        'workflow_instance_stage_id' => $activeStage->id,
        'action' => 'reject',
        'comment' => 'Not approved.',
    ]);
});
test('send back records action and stores sent back stage id', function () {
    ['template' => $template] = makeSequentialTemplate(2);
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
    $this->engine->sendBack($activeStage, $this->user, 'Please fix the amount.');

    $activeStage->refresh();
    expect($activeStage->status)->toEqual('sent_back');

    $instance->refresh();
    expect($instance->sent_back_to_stage_id)->toEqual($activeStage->id);

    $subject->refresh();
    expect($subject->status)->toEqual('sent_back');

    $this->assertDatabaseHas('workflow_actions', [
        'workflow_instance_stage_id' => $activeStage->id,
        'action' => 'send_back',
        'comment' => 'Please fix the amount.',
    ]);
});
test('send back in parallel group cancels sibling stages', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $group = WorkflowParallelGroup::factory()->requireAll()->create(['workflow_template_id' => $template->id]);
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s1->roles()->sync([$role->id]);
    $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s2->roles()->sync([$role->id]);

    $template->load('stages');
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    $stage1 = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
    $stage2 = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();

    $this->engine->sendBack($stage1, $this->user, 'Needs revision.');

    $stage1->refresh();
    $stage2->refresh();

    expect($stage1->status)->toEqual('sent_back');
    expect($stage2->status)->toEqual('cancelled');

    $subject->refresh();
    expect($subject->status)->toEqual('sent_back');

    $instance->refresh();
    expect($instance->sent_back_to_stage_id)->toEqual($stage1->id);
});
test('resubmit after fix reactivates sent back stage and clears flag', function () {
    ['template' => $template] = makeSequentialTemplate(2);
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
    $this->engine->sendBack($activeStage, $this->user, 'Fix needed.');

    $subject->refresh();
    $this->engine->resubmitAfterFix($subject, $this->user);

    $activeStage->refresh();
    expect($activeStage->status)->toEqual('active');
    expect($activeStage->started_at)->not->toBeNull();

    $instance->refresh();
    expect($instance->sent_back_to_stage_id)->toBeNull();

    $subject->refresh();
    expect($subject->status)->toEqual('in_workflow');
});
test('resubmit after fix reactivates cancelled parallel siblings', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $group = WorkflowParallelGroup::factory()->requireAll()->create(['workflow_template_id' => $template->id]);
    $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s1->roles()->sync([$role->id]);
    $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s2->roles()->sync([$role->id]);

    $template->load('stages');
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    $stage1 = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
    $stage2 = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();

    // Send back stage1 — stage2 gets cancelled
    $this->engine->sendBack($stage1, $this->user, 'Fix needed.');

    $subject->refresh();
    $this->engine->resubmitAfterFix($subject, $this->user);

    $stage1->refresh();
    $stage2->refresh();

    expect($stage1->status)->toEqual('active');
    expect($stage2->status)->toEqual('active');

    $subject->refresh();
    expect($subject->status)->toEqual('in_workflow');

    $instance->refresh();
    expect($instance->sent_back_to_stage_id)->toBeNull();
});
test('stage is auto skipped when submitter has stage role', function () {
    ['template' => $template, 'role' => $role, 'stages' => $stages] = makeSequentialTemplate(1);
    $subject = makePaymentRequestForWorkflowEngineService();
    $this->user->assignRole($role);

    $instance = $this->engine->startWorkflow($subject, $template, $this->user);

    $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();
    expect($instanceStage->status)->toEqual('skipped');
    expect($instanceStage->completed_at)->not->toBeNull();
});
test('stage is not auto skipped when submitter lacks stage role', function () {
    ['template' => $template, 'stages' => $stages] = makeSequentialTemplate(1);
    $subject = makePaymentRequestForWorkflowEngineService();

    $instance = $this->engine->startWorkflow($subject, $template, $this->user);

    $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();
    expect($instanceStage->status)->toEqual('active');
});
test('all stages auto skipped completes workflow', function () {
    ['template' => $template, 'role' => $role] = makeSequentialTemplate(2);
    $subject = makePaymentRequestForWorkflowEngineService(['type' => App\Enums\Tenant\PaymentRequestType::Advance->value]);
    $this->user->assignRole($role);

    $instance = $this->engine->startWorkflow($subject, $template, $this->user);

    $instance->refresh();
    expect($instance->status)->toEqual('completed');

    $subject->refresh();
    expect($subject->status)->toEqual('approved');
    expect($subject->approved_at)->not->toBeNull();
});
test('submitter skip advances to next sequential stage', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $role1 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
    $role2 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);
    $s1->roles()->sync([$role1->id]);

    $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 2]);
    $s2->roles()->sync([$role2->id]);

    $template->load('stages');
    $subject = makePaymentRequestForWorkflowEngineService();
    $this->user->assignRole($role1);

    $instance = $this->engine->startWorkflow($subject, $template, $this->user);

    $instanceStage1 = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
    $instanceStage2 = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();

    expect($instanceStage1->status)->toEqual('skipped');
    expect($instanceStage2->status)->toEqual('active');
});
test('submitter skip in parallel group', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $group = WorkflowParallelGroup::factory()->requireAll()->create(['workflow_template_id' => $template->id]);
    $role1 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
    $role2 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

    $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s1->roles()->sync([$role1->id]);

    $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
    $s2->roles()->sync([$role2->id]);

    $template->load('stages');
    $subject = makePaymentRequestForWorkflowEngineService();
    $this->user->assignRole($role1);

    $instance = $this->engine->startWorkflow($subject, $template, $this->user);

    $instanceStage1 = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
    $instanceStage2 = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();

    expect($instanceStage1->status)->toEqual('skipped');
    expect($instanceStage2->status)->toEqual('active');
});
test('no submitter preserves existing behavior', function () {
    ['template' => $template, 'role' => $role, 'stages' => $stages] = makeSequentialTemplate(1);
    $subject = makePaymentRequestForWorkflowEngineService();
    $this->user->assignRole($role);

    // Pass null submitter — stage must activate even though $this->user has the role
    $instance = $this->engine->startWorkflow($subject, $template, null);

    $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();
    expect($instanceStage->status)->toEqual('active');
    $this->assertDatabaseHas('workflow_instances', ['id' => $instance->id, 'submitter_user_id' => null]);
});
test('can user act on stage returns true when user has stage role', function () {
    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    $this->user->assignRole($role);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $this->user))->toBeTrue();
});
test('can user act on stage returns false when user lacks stage role', function () {
    ['template' => $template] = makeSequentialTemplate(1);
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);

    // Create a different user with no relevant roles
    $otherUser = User::factory()->create();
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $otherUser))->toBeFalse();
});
test('department scoped stage allows approver in same department', function () {
    $dept = Department::factory()->create();
    $submitter = makeUserWithStaff(departmentId: $dept->id);
    $approver = makeUserWithStaff(departmentId: $dept->id);

    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
    $approver->assignRole($role);

    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeTrue();
});
test('department scoped stage blocks approver in different department', function () {
    $submitter = makeUserWithStaff(departmentId: Department::factory()->create()->id);
    $approver = makeUserWithStaff(departmentId: Department::factory()->create()->id);

    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
    $approver->assignRole($role);

    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeFalse();
});
test('department scoped stage allows approver with no staff profile', function () {
    $submitter = makeUserWithStaff(departmentId: Department::factory()->create()->id);
    $approver = User::factory()->create();

    // no Staff record — bypasses scope filters
    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
    $approver->assignRole($role);

    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeTrue();
});
test('department scoped stage blocks when submitter has no staff profile', function () {
    $submitter = User::factory()->create();
    // no Staff record
    $approver = makeUserWithStaff(departmentId: Department::factory()->create()->id);

    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
    $approver->assignRole($role);

    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeFalse();
});
test('branch scoped stage allows approver in same branch', function () {
    $approver = makeUserWithStaff(branchId: $this->branch->id);

    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
    $approver->assignRole($role);

    // Request is in $this->branch
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeTrue();
});
test('branch scoped stage blocks approver in different branch', function () {
    $otherBranch = Branch::factory()->create();
    $approver = makeUserWithStaff(branchId: $otherBranch->id);

    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
    $approver->assignRole($role);

    // Request is in $this->branch (different from approver's branch)
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeFalse();
});
test('branch scoped stage blocks approver with no branch', function () {
    $approver = makeUserWithStaff(branchId: null);

    // staff exists but no branch
    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
    $approver->assignRole($role);

    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeFalse();
});
test('both scopes allow when department and branch match', function () {
    $dept = Department::factory()->create();
    $submitter = makeUserWithStaff(departmentId: $dept->id, branchId: $this->branch->id);
    $approver = makeUserWithStaff(departmentId: $dept->id, branchId: $this->branch->id);

    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update([
        'scope_to_department' => true,
        'scope_to_branch' => true,
    ]);
    $approver->assignRole($role);

    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeTrue();
});
test('both scopes block when branch mismatches', function () {
    $dept = Department::factory()->create();
    $submitter = makeUserWithStaff(departmentId: $dept->id, branchId: $this->branch->id);
    $otherBranch = Branch::factory()->create();
    $approver = makeUserWithStaff(departmentId: $dept->id, branchId: $otherBranch->id);

    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update([
        'scope_to_department' => true,
        'scope_to_branch' => true,
    ]);
    $approver->assignRole($role);

    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeFalse();
});
test('both scopes block when department mismatches', function () {
    $dept = Department::factory()->create();
    $submitter = makeUserWithStaff(departmentId: $dept->id, branchId: $this->branch->id);
    $approver = makeUserWithStaff(departmentId: Department::factory()->create()->id, branchId: $this->branch->id);

    ['template' => $template, 'role' => $role] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update([
        'scope_to_department' => true,
        'scope_to_branch' => true,
    ]);
    $approver->assignRole($role);

    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

    expect($this->engine->canUserActOnStage($activeStage, $approver))->toBeFalse();
});
test('branch scoped submitter not auto skipped when branch differs', function () {
    $otherBranch = Branch::factory()->create();
    $submitter = makeUserWithStaff(branchId: $otherBranch->id);

    ['template' => $template, 'role' => $role, 'stages' => $stages] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
    $submitter->assignRole($role);

    // Request branch is $this->branch, submitter branch is $otherBranch
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();

    expect($instanceStage->status)->toEqual('active');
});
test('branch scoped submitter is auto skipped when branch matches', function () {
    $submitter = makeUserWithStaff(branchId: $this->branch->id);

    ['template' => $template, 'role' => $role, 'stages' => $stages] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
    $submitter->assignRole($role);

    // Request branch is $this->branch, submitter branch also $this->branch
    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();

    expect($instanceStage->status)->toEqual('skipped');
});
test('department scoped submitter not auto skipped when no staff profile', function () {
    $submitter = User::factory()->create();

    // no Staff record
    ['template' => $template, 'role' => $role, 'stages' => $stages] = makeSequentialTemplate(1);
    WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
    $submitter->assignRole($role);

    $subject = makePaymentRequestForWorkflowEngineService();
    $instance = $this->engine->startWorkflow($subject, $template, $submitter);
    $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();

    expect($instanceStage->status)->toEqual('active');
});
// ── Helpers ───────────────────────────────────────────────────────────────
function makeUserWithStaff(int|null $departmentId = null, int|null $branchId = null): User
{
    $user = User::factory()->create();
    Staff::factory()->withUser($user)->create([
        'department_id' => $departmentId ?? Department::factory()->create()->id,
        'branch_id' => $branchId,
    ]);

    return $user->fresh('staffProfile');
}
