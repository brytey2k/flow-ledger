<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

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
use Tests\TenantAppTestCase;

class WorkflowEngineServiceTest extends TenantAppTestCase
{
    private WorkflowEngineService $engine;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = app(WorkflowEngineService::class);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Build a PaymentRequest with all required FK relations resolved.
     *
     * @param array<string, mixed> $overrides
     */
    private function makePaymentRequest(array $overrides = []): PaymentRequest
    {
        $currency = Currency::factory()->create();

        return PaymentRequest::factory()->inWorkflow()->create(array_merge([
            'branch_id' => $this->branch->id,
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
    private function makeSequentialTemplate(int $stageCount = 2): array
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

    // ── startWorkflow ─────────────────────────────────────────────────────────

    public function test_start_workflow_creates_instance_and_pending_stages(): void
    {
        ['template' => $template, 'stages' => $stages] = $this->makeSequentialTemplate(2);
        $subject = $this->makePaymentRequest();

        $instance = $this->engine->startWorkflow($subject, $template);

        $this->assertDatabaseHas('workflow_instances', [
            'id' => $instance->id,
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $subject->id,
            'status' => 'in_progress',
        ]);

        $this->assertDatabaseCount('workflow_instance_stages', 2);
    }

    public function test_start_workflow_activates_first_stage_only(): void
    {
        ['template' => $template] = $this->makeSequentialTemplate(3);
        $subject = $this->makePaymentRequest();

        $this->engine->startWorkflow($subject, $template);

        $this->assertDatabaseHas('workflow_instance_stages', ['status' => 'active']);
        $this->assertEquals(1, WorkflowInstanceStage::where('status', 'active')->count());
        $this->assertEquals(2, WorkflowInstanceStage::where('status', 'pending')->count());
    }

    public function test_start_workflow_activates_all_stages_in_first_parallel_group(): void
    {
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
        $subject = $this->makePaymentRequest();

        $this->engine->startWorkflow($subject, $template);

        $this->assertEquals(2, WorkflowInstanceStage::where('status', 'active')->count());
        $this->assertEquals(1, WorkflowInstanceStage::where('status', 'pending')->count());
    }

    // ── activateNextStages (threshold) ────────────────────────────────────────

    public function test_stage_below_threshold_is_skipped_and_next_is_activated(): void
    {
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
        $subject = $this->makePaymentRequest(['total_amount' => 1000.00]);

        $this->engine->startWorkflow($subject, $template);

        $instanceStage1 = WorkflowInstanceStage::where('workflow_stage_id', $s1->id)->firstOrFail();
        $instanceStage2 = WorkflowInstanceStage::where('workflow_stage_id', $s2->id)->firstOrFail();

        $this->assertEquals('skipped', $instanceStage1->status);
        $this->assertEquals('active', $instanceStage2->status);
    }

    public function test_stage_meeting_threshold_is_not_skipped(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

        $stage = WorkflowStage::factory()->withThreshold(500)->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        $stage->roles()->sync([$role->id]);

        $template->load('stages');
        $subject = $this->makePaymentRequest(['total_amount' => 1000.00]);

        $this->engine->startWorkflow($subject, $template);

        $instanceStage = WorkflowInstanceStage::where('workflow_stage_id', $stage->id)->firstOrFail();
        $this->assertEquals('active', $instanceStage->status);
    }

    // ── approve (sequential) ──────────────────────────────────────────────────

    public function test_approve_first_stage_activates_next_stage(): void
    {
        ['template' => $template, 'stages' => $stages] = $this->makeSequentialTemplate(2);
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
        $this->engine->approve($activeStage, $this->user, 'Looks good');

        $activeStage->refresh();
        $this->assertEquals('approved', $activeStage->status);
        $this->assertNotNull($activeStage->completed_at);

        $this->assertEquals(1, WorkflowInstanceStage::where('status', 'active')->count());
        $this->assertDatabaseHas('workflow_actions', [
            'workflow_instance_stage_id' => $activeStage->id,
            'user_id' => $this->user->id,
            'action' => 'approve',
            'comment' => 'Looks good',
        ]);
    }

    public function test_approve_last_stage_completes_workflow_and_marks_subject_approved(): void
    {
        ['template' => $template] = $this->makeSequentialTemplate(1);
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
        $this->engine->approve($activeStage, $this->user);

        $instance->refresh();
        $this->assertEquals('completed', $instance->status);

        $subject->refresh();
        $this->assertEquals('approved', $subject->status);
        $this->assertNotNull($subject->approved_at);
    }

    // ── approve (parallel AND) ────────────────────────────────────────────────

    public function test_parallel_and_group_waits_for_all_siblings_before_advancing(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $group = WorkflowParallelGroup::factory()->requireAll()->create(['workflow_template_id' => $template->id]);
        $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

        $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s1->roles()->sync([$role->id]);
        $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s2->roles()->sync([$role->id]);

        $template->load('stages');
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        // Approve only the first sibling
        $firstActive = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
        $this->engine->approve($firstActive, $this->user);

        // Workflow should still be in progress — second sibling still active
        $instance->refresh();
        $this->assertEquals('in_progress', $instance->status);
        $this->assertEquals(1, WorkflowInstanceStage::where('status', 'active')->count());

        // Now approve the second sibling
        $secondActive = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();
        $this->engine->approve($secondActive, $this->user);

        $instance->refresh();
        $this->assertEquals('completed', $instance->status);
    }

    // ── approve (parallel OR) ─────────────────────────────────────────────────

    public function test_parallel_or_group_cancels_siblings_on_first_approval(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $group = WorkflowParallelGroup::factory()->requireAny()->create(['workflow_template_id' => $template->id]);
        $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

        $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s1->roles()->sync([$role->id]);
        $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s2->roles()->sync([$role->id]);

        $template->load('stages');
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        $firstActive = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
        $this->engine->approve($firstActive, $this->user);

        // Sibling should be cancelled
        $sibling = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();
        $this->assertEquals('cancelled', $sibling->status);

        // Workflow completes immediately (no more stages)
        $instance->refresh();
        $this->assertEquals('completed', $instance->status);
    }

    // ── reject ────────────────────────────────────────────────────────────────

    public function test_reject_cancels_all_remaining_stages_and_marks_subject_cancelled(): void
    {
        ['template' => $template] = $this->makeSequentialTemplate(3);
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
        $this->engine->reject($activeStage, $this->user, 'Not approved.');

        $activeStage->refresh();
        $this->assertEquals('rejected', $activeStage->status);

        $this->assertEquals(0, WorkflowInstanceStage::whereIn('status', ['pending', 'active'])->count());

        $instance->refresh();
        $this->assertEquals('cancelled', $instance->status);

        $subject->refresh();
        $this->assertEquals('cancelled', $subject->status);

        $this->assertDatabaseHas('workflow_actions', [
            'workflow_instance_stage_id' => $activeStage->id,
            'action' => 'reject',
            'comment' => 'Not approved.',
        ]);
    }

    // ── sendBack ──────────────────────────────────────────────────────────────

    public function test_send_back_records_action_and_stores_sent_back_stage_id(): void
    {
        ['template' => $template] = $this->makeSequentialTemplate(2);
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
        $this->engine->sendBack($activeStage, $this->user, 'Please fix the amount.');

        $activeStage->refresh();
        $this->assertEquals('sent_back', $activeStage->status);

        $instance->refresh();
        $this->assertEquals($activeStage->id, $instance->sent_back_to_stage_id);

        $subject->refresh();
        $this->assertEquals('sent_back', $subject->status);

        $this->assertDatabaseHas('workflow_actions', [
            'workflow_instance_stage_id' => $activeStage->id,
            'action' => 'send_back',
            'comment' => 'Please fix the amount.',
        ]);
    }

    public function test_send_back_in_parallel_group_cancels_sibling_stages(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $group = WorkflowParallelGroup::factory()->requireAll()->create(['workflow_template_id' => $template->id]);
        $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

        $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s1->roles()->sync([$role->id]);
        $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s2->roles()->sync([$role->id]);

        $template->load('stages');
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        $stage1 = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
        $stage2 = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();

        $this->engine->sendBack($stage1, $this->user, 'Needs revision.');

        $stage1->refresh();
        $stage2->refresh();

        $this->assertEquals('sent_back', $stage1->status);
        $this->assertEquals('cancelled', $stage2->status);

        $subject->refresh();
        $this->assertEquals('sent_back', $subject->status);

        $instance->refresh();
        $this->assertEquals($stage1->id, $instance->sent_back_to_stage_id);
    }

    // ── resubmitAfterFix ──────────────────────────────────────────────────────

    public function test_resubmit_after_fix_reactivates_sent_back_stage_and_clears_flag(): void
    {
        ['template' => $template] = $this->makeSequentialTemplate(2);
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
        $this->engine->sendBack($activeStage, $this->user, 'Fix needed.');

        $subject->refresh();
        $this->engine->resubmitAfterFix($subject, $this->user);

        $activeStage->refresh();
        $this->assertEquals('active', $activeStage->status);
        $this->assertNotNull($activeStage->started_at);

        $instance->refresh();
        $this->assertNull($instance->sent_back_to_stage_id);

        $subject->refresh();
        $this->assertEquals('in_workflow', $subject->status);
    }

    public function test_resubmit_after_fix_reactivates_cancelled_parallel_siblings(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $group = WorkflowParallelGroup::factory()->requireAll()->create(['workflow_template_id' => $template->id]);
        $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

        $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s1->roles()->sync([$role->id]);
        $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s2->roles()->sync([$role->id]);

        $template->load('stages');
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        $stage1 = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
        $stage2 = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();

        // Send back stage1 — stage2 gets cancelled
        $this->engine->sendBack($stage1, $this->user, 'Fix needed.');

        $subject->refresh();
        $this->engine->resubmitAfterFix($subject, $this->user);

        $stage1->refresh();
        $stage2->refresh();

        $this->assertEquals('active', $stage1->status);
        $this->assertEquals('active', $stage2->status);

        $subject->refresh();
        $this->assertEquals('in_workflow', $subject->status);

        $instance->refresh();
        $this->assertNull($instance->sent_back_to_stage_id);
    }

    // ── activateNextStages (submitter auto-skip) ──────────────────────────────

    public function test_stage_is_auto_skipped_when_submitter_has_stage_role(): void
    {
        ['template' => $template, 'role' => $role, 'stages' => $stages] = $this->makeSequentialTemplate(1);
        $subject = $this->makePaymentRequest();
        $this->user->assignRole($role);

        $instance = $this->engine->startWorkflow($subject, $template, $this->user);

        $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();
        $this->assertEquals('skipped', $instanceStage->status);
        $this->assertNotNull($instanceStage->completed_at);
    }

    public function test_stage_is_not_auto_skipped_when_submitter_lacks_stage_role(): void
    {
        ['template' => $template, 'stages' => $stages] = $this->makeSequentialTemplate(1);
        $subject = $this->makePaymentRequest();

        $instance = $this->engine->startWorkflow($subject, $template, $this->user);

        $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();
        $this->assertEquals('active', $instanceStage->status);
    }

    public function test_all_stages_auto_skipped_completes_workflow(): void
    {
        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(2);
        $subject = $this->makePaymentRequest();
        $this->user->assignRole($role);

        $instance = $this->engine->startWorkflow($subject, $template, $this->user);

        $instance->refresh();
        $this->assertEquals('completed', $instance->status);

        $subject->refresh();
        $this->assertEquals('approved', $subject->status);
        $this->assertNotNull($subject->approved_at);
    }

    public function test_submitter_skip_advances_to_next_sequential_stage(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $role1 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
        $role2 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

        $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);
        $s1->roles()->sync([$role1->id]);

        $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 2]);
        $s2->roles()->sync([$role2->id]);

        $template->load('stages');
        $subject = $this->makePaymentRequest();
        $this->user->assignRole($role1);

        $instance = $this->engine->startWorkflow($subject, $template, $this->user);

        $instanceStage1 = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
        $instanceStage2 = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();

        $this->assertEquals('skipped', $instanceStage1->status);
        $this->assertEquals('active', $instanceStage2->status);
    }

    public function test_submitter_skip_in_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $group = WorkflowParallelGroup::factory()->requireAll()->create(['workflow_template_id' => $template->id]);
        $role1 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
        $role2 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);

        $s1 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s1->roles()->sync([$role1->id]);

        $s2 = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'parallel_group_id' => $group->id, 'display_order' => 1]);
        $s2->roles()->sync([$role2->id]);

        $template->load('stages');
        $subject = $this->makePaymentRequest();
        $this->user->assignRole($role1);

        $instance = $this->engine->startWorkflow($subject, $template, $this->user);

        $instanceStage1 = $instance->instanceStages()->where('workflow_stage_id', $s1->id)->firstOrFail();
        $instanceStage2 = $instance->instanceStages()->where('workflow_stage_id', $s2->id)->firstOrFail();

        $this->assertEquals('skipped', $instanceStage1->status);
        $this->assertEquals('active', $instanceStage2->status);
    }

    public function test_no_submitter_preserves_existing_behavior(): void
    {
        ['template' => $template, 'role' => $role, 'stages' => $stages] = $this->makeSequentialTemplate(1);
        $subject = $this->makePaymentRequest();
        $this->user->assignRole($role);

        // Pass null submitter — stage must activate even though $this->user has the role
        $instance = $this->engine->startWorkflow($subject, $template, null);

        $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();
        $this->assertEquals('active', $instanceStage->status);
        $this->assertDatabaseHas('workflow_instances', ['id' => $instance->id, 'submitter_user_id' => null]);
    }

    // ── canUserActOnStage ─────────────────────────────────────────────────────

    public function test_can_user_act_on_stage_returns_true_when_user_has_stage_role(): void
    {
        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        $this->user->assignRole($role);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertTrue($this->engine->canUserActOnStage($activeStage, $this->user));
    }

    public function test_can_user_act_on_stage_returns_false_when_user_lacks_stage_role(): void
    {
        ['template' => $template] = $this->makeSequentialTemplate(1);
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);

        // Create a different user with no relevant roles
        $otherUser = User::factory()->create();
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertFalse($this->engine->canUserActOnStage($activeStage, $otherUser));
    }

    // ── canUserActOnStage (department scoping) ────────────────────────────────

    public function test_department_scoped_stage_allows_approver_in_same_department(): void
    {
        $dept = Department::factory()->create();
        $submitter = $this->makeUserWithStaff(departmentId: $dept->id);
        $approver = $this->makeUserWithStaff(departmentId: $dept->id);

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
        $approver->assignRole($role);

        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertTrue($this->engine->canUserActOnStage($activeStage, $approver));
    }

    public function test_department_scoped_stage_blocks_approver_in_different_department(): void
    {
        $submitter = $this->makeUserWithStaff(departmentId: Department::factory()->create()->id);
        $approver = $this->makeUserWithStaff(departmentId: Department::factory()->create()->id);

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
        $approver->assignRole($role);

        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertFalse($this->engine->canUserActOnStage($activeStage, $approver));
    }

    public function test_department_scoped_stage_blocks_approver_with_no_staff_profile(): void
    {
        $submitter = $this->makeUserWithStaff(departmentId: Department::factory()->create()->id);
        $approver = User::factory()->create(); // no Staff record

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
        $approver->assignRole($role);

        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertFalse($this->engine->canUserActOnStage($activeStage, $approver));
    }

    public function test_department_scoped_stage_blocks_when_submitter_has_no_staff_profile(): void
    {
        $submitter = User::factory()->create(); // no Staff record
        $approver = $this->makeUserWithStaff(departmentId: Department::factory()->create()->id);

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
        $approver->assignRole($role);

        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertFalse($this->engine->canUserActOnStage($activeStage, $approver));
    }

    // ── canUserActOnStage (branch scoping) ────────────────────────────────────

    public function test_branch_scoped_stage_allows_approver_in_same_branch(): void
    {
        $approver = $this->makeUserWithStaff(branchId: $this->branch->id);

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
        $approver->assignRole($role);

        // Request is in $this->branch
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertTrue($this->engine->canUserActOnStage($activeStage, $approver));
    }

    public function test_branch_scoped_stage_blocks_approver_in_different_branch(): void
    {
        $otherBranch = Branch::factory()->create();
        $approver = $this->makeUserWithStaff(branchId: $otherBranch->id);

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
        $approver->assignRole($role);

        // Request is in $this->branch (different from approver's branch)
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertFalse($this->engine->canUserActOnStage($activeStage, $approver));
    }

    public function test_branch_scoped_stage_blocks_approver_with_no_branch(): void
    {
        $approver = $this->makeUserWithStaff(branchId: null); // staff exists but no branch

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
        $approver->assignRole($role);

        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertFalse($this->engine->canUserActOnStage($activeStage, $approver));
    }

    // ── canUserActOnStage (combined scoping) ──────────────────────────────────

    public function test_both_scopes_allow_when_department_and_branch_match(): void
    {
        $dept = Department::factory()->create();
        $submitter = $this->makeUserWithStaff(departmentId: $dept->id, branchId: $this->branch->id);
        $approver = $this->makeUserWithStaff(departmentId: $dept->id, branchId: $this->branch->id);

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update([
            'scope_to_department' => true,
            'scope_to_branch' => true,
        ]);
        $approver->assignRole($role);

        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertTrue($this->engine->canUserActOnStage($activeStage, $approver));
    }

    public function test_both_scopes_block_when_branch_mismatches(): void
    {
        $dept = Department::factory()->create();
        $submitter = $this->makeUserWithStaff(departmentId: $dept->id, branchId: $this->branch->id);
        $otherBranch = Branch::factory()->create();
        $approver = $this->makeUserWithStaff(departmentId: $dept->id, branchId: $otherBranch->id);

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update([
            'scope_to_department' => true,
            'scope_to_branch' => true,
        ]);
        $approver->assignRole($role);

        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertFalse($this->engine->canUserActOnStage($activeStage, $approver));
    }

    public function test_both_scopes_block_when_department_mismatches(): void
    {
        $dept = Department::factory()->create();
        $submitter = $this->makeUserWithStaff(departmentId: $dept->id, branchId: $this->branch->id);
        $approver = $this->makeUserWithStaff(departmentId: Department::factory()->create()->id, branchId: $this->branch->id);

        ['template' => $template, 'role' => $role] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update([
            'scope_to_department' => true,
            'scope_to_branch' => true,
        ]);
        $approver->assignRole($role);

        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();

        $this->assertFalse($this->engine->canUserActOnStage($activeStage, $approver));
    }

    // ── activateNextStages (submitter auto-skip with scoping) ─────────────────

    public function test_branch_scoped_submitter_not_auto_skipped_when_branch_differs(): void
    {
        $otherBranch = Branch::factory()->create();
        $submitter = $this->makeUserWithStaff(branchId: $otherBranch->id);

        ['template' => $template, 'role' => $role, 'stages' => $stages] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
        $submitter->assignRole($role);

        // Request branch is $this->branch, submitter branch is $otherBranch
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();

        $this->assertEquals('active', $instanceStage->status);
    }

    public function test_branch_scoped_submitter_is_auto_skipped_when_branch_matches(): void
    {
        $submitter = $this->makeUserWithStaff(branchId: $this->branch->id);

        ['template' => $template, 'role' => $role, 'stages' => $stages] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_branch' => true]);
        $submitter->assignRole($role);

        // Request branch is $this->branch, submitter branch also $this->branch
        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();

        $this->assertEquals('skipped', $instanceStage->status);
    }

    public function test_department_scoped_submitter_not_auto_skipped_when_no_staff_profile(): void
    {
        $submitter = User::factory()->create(); // no Staff record

        ['template' => $template, 'role' => $role, 'stages' => $stages] = $this->makeSequentialTemplate(1);
        WorkflowStage::where('workflow_template_id', $template->id)->update(['scope_to_department' => true]);
        $submitter->assignRole($role);

        $subject = $this->makePaymentRequest();
        $instance = $this->engine->startWorkflow($subject, $template, $submitter);
        $instanceStage = $instance->instanceStages()->where('workflow_stage_id', $stages[0]->id)->firstOrFail();

        $this->assertEquals('active', $instanceStage->status);
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeUserWithStaff(
        int|null $departmentId = null,
        int|null $branchId = null,
    ): User {
        $user = User::factory()->create();
        Staff::factory()->withUser($user)->create([
            'department_id' => $departmentId ?? Department::factory()->create()->id,
            'branch_id' => $branchId,
        ]);

        return $user->fresh('staffProfile');
    }
}
