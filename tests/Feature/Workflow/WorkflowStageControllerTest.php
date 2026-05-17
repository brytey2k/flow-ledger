<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

use App\Models\Role;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Tests\TenantAppTestCase;

class WorkflowStageControllerTest extends TenantAppTestCase
{
    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_create_form(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->get(route('workflow-templates.stages.create', $template));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_edit_permission_cannot_access_create_form(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $this->role->revokePermissionTo('edit workflow template');

        $response = $this->actingAs($this->user)
            ->get(route('workflow-templates.stages.create', $template));

        $response->assertForbidden();
    }

    public function test_user_without_edit_permission_cannot_store_stage(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $this->role->revokePermissionTo('edit workflow template');

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), []);

        $response->assertForbidden();
    }

    public function test_user_without_edit_permission_cannot_delete_stage(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $this->role->revokePermissionTo('edit workflow template');

        $response = $this->actingAs($this->user)
            ->delete(route('workflow-templates.stages.destroy', [$template, $stage]));

        $response->assertForbidden();
    }

    // ── Create form ───────────────────────────────────────────────────────────

    public function test_create_form_returns_ok_with_roles_and_parallel_groups(): void
    {
        $template = WorkflowTemplate::factory()->create();
        WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-templates.stages.create', $template));

        $response->assertOk();
        $response->assertViewHas('workflowTemplate');
        $response->assertViewHas('roles');
        $response->assertViewHas('parallelGroups');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_user_can_create_stage_with_roles(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $role = Role::create(['name' => 'approver', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), [
                'name' => 'Finance Review',
                'display_order' => 1,
                'skip_below_amount' => null,
                'parallel_group_id' => null,
                'role_ids' => [$role->id],
            ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHasNoErrors();

        $stage = WorkflowStage::where('name', 'Finance Review')->firstOrFail();
        $this->assertDatabaseHas('workflow_stages', [
            'workflow_template_id' => $template->id,
            'name' => 'Finance Review',
            'display_order' => 1,
        ]);
        $this->assertDatabaseHas('workflow_stage_roles', [
            'workflow_stage_id' => $stage->id,
            'role_id' => $role->id,
        ]);
    }

    public function test_store_requires_name(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $role = Role::create(['name' => 'approver_b', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), [
                'display_order' => 1,
                'role_ids' => [$role->id],
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_requires_role_ids(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), [
                'name' => 'Stage',
                'display_order' => 1,
                'role_ids' => [],
            ]);

        $response->assertSessionHasErrors('role_ids');
    }

    public function test_store_requires_display_order(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $role = Role::create(['name' => 'approver_c', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), [
                'name' => 'Stage',
                'role_ids' => [$role->id],
            ]);

        $response->assertSessionHasErrors('display_order');
    }

    public function test_store_accepts_skip_below_amount(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $role = Role::create(['name' => 'approver_d', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), [
                'name' => 'Low Value Stage',
                'display_order' => 1,
                'skip_below_amount' => 500.00,
                'role_ids' => [$role->id],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workflow_stages', [
            'name' => 'Low Value Stage',
            'skip_below_amount' => 500.00,
        ]);
    }

    public function test_store_accepts_parallel_group_id(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        $role = Role::create(['name' => 'approver_e', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), [
                'name' => 'Parallel Stage',
                'display_order' => 1,
                'parallel_group_id' => $group->id,
                'role_ids' => [$role->id],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workflow_stages', [
            'name' => 'Parallel Stage',
            'parallel_group_id' => $group->id,
        ]);
    }

    // ── Edit form ─────────────────────────────────────────────────────────────

    public function test_edit_form_returns_ok_with_stage_data(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-templates.stages.edit', [$template, $stage]));

        $response->assertOk();
        $response->assertViewHas('workflowTemplate');
        $response->assertViewHas('workflowStage');
        $response->assertViewHas('roles');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_stage_and_sync_roles(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $oldRole = Role::create(['name' => 'old_role', 'guard_name' => 'web']);
        $newRole = Role::create(['name' => 'new_role', 'guard_name' => 'web']);
        $stage->roles()->sync([$oldRole->id]);

        $response = $this->actingAs($this->user)
            ->put(route('workflow-templates.stages.update', [$template, $stage]), [
                'name' => 'Updated Stage Name',
                'display_order' => 2,
                'skip_below_amount' => null,
                'role_ids' => [$newRole->id],
            ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_stages', ['id' => $stage->id, 'name' => 'Updated Stage Name', 'display_order' => 2]);
        $this->assertDatabaseHas('workflow_stage_roles', ['workflow_stage_id' => $stage->id, 'role_id' => $newRole->id]);
        $this->assertDatabaseMissing('workflow_stage_roles', ['workflow_stage_id' => $stage->id, 'role_id' => $oldRole->id]);
    }

    // ── Store blocked by active instances ────────────────────────────────────

    public function test_store_is_blocked_when_template_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $role = Role::create(['name' => 'approver_lock', 'guard_name' => 'web']);
        $subject = PaymentRequest::factory()->inWorkflow()->create();
        WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $subject->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), [
                'name' => 'Blocked Stage',
                'display_order' => 1,
                'role_ids' => [$role->id],
            ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('workflow_stages', ['name' => 'Blocked Stage']);
    }

    // ── Update blocked by active instances ───────────────────────────────────

    public function test_update_is_blocked_when_template_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $role = Role::create(['name' => 'update_lock_role', 'guard_name' => 'web']);
        $subject = PaymentRequest::factory()->inWorkflow()->create();
        WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $subject->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)
            ->put(route('workflow-templates.stages.update', [$template, $stage]), [
                'name' => 'Should Not Update',
                'display_order' => 1,
                'role_ids' => [$role->id],
            ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('workflow_stages', ['name' => 'Should Not Update']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_stage(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('workflow-templates.stages.destroy', [$template, $stage]));

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseMissing('workflow_stages', ['id' => $stage->id]);
    }

    public function test_destroy_is_blocked_when_template_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $subject = PaymentRequest::factory()->inWorkflow()->create();
        WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $subject->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('workflow-templates.stages.destroy', [$template, $stage]));

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('workflow_stages', ['id' => $stage->id]);
    }
}
