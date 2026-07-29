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

    public function test_create_form_exposes_existing_group_members_for_client_side_sync_check(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        $existing = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
            'name' => 'Existing Sibling',
            'display_order' => 4,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-templates.stages.create', $template));

        $response->assertViewHas('parallelGroupStages', fn(array $data) => $data[$group->id][0] === [
            'id' => $existing->id,
            'name' => 'Existing Sibling',
            'display_order' => 4,
        ]);
    }

    /**
     * Regression test: @json() only escapes quote characters found inside the data
     * itself, not the structural quotes of the JSON syntax — so embedding it in a
     * double-quoted x-data="..." attribute breaks the page the moment the JSON's own
     * `"key":` syntax appears. The attribute must be single-quoted so JSON_HEX_APOS
     * neutralizes any apostrophes in stage names instead of terminating the attribute.
     */
    public function test_create_page_renders_stage_names_with_quotes_safely_in_alpine_data(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
            'name' => 'Manager\'s "Special" Review',
            'display_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-templates.stages.create', $template));

        $response->assertOk();
        $response->assertSee("x-data='workflowStageForm(", false);
        preg_match('/x-data=\'workflowStageForm\((.*?), null\)\'/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $decoded = json_decode($matches[1], true);
        $this->assertSame('Manager\'s "Special" Review', $decoded[$group->id][0]['name']);
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

    public function test_store_syncs_display_order_of_existing_group_members(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        $existing = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
            'display_order' => 1,
        ]);
        $role = Role::create(['name' => 'approver_sync', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), [
                'name' => 'New Parallel Sibling',
                'display_order' => 6,
                'parallel_group_id' => $group->id,
                'role_ids' => [$role->id],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workflow_stages', ['id' => $existing->id, 'display_order' => 6]);
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

    public function test_edit_form_exposes_parallel_group_stages_for_client_side_sync_check(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
            'display_order' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-templates.stages.edit', [$template, $stage]));

        $response->assertViewHas('parallelGroupStages', fn(array $data) => count($data[$group->id]) === 1
            && $data[$group->id][0]['id'] === $stage->id);
    }

    /** @see test_create_page_renders_stage_names_with_quotes_safely_in_alpine_data for why this matters */
    public function test_edit_page_renders_stage_names_with_quotes_safely_in_alpine_data(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
            'name' => 'Manager\'s "Special" Review',
            'display_order' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('workflow-templates.stages.edit', [$template, $stage]));

        $response->assertOk();
        $response->assertSee("x-data='workflowStageForm(", false);
        preg_match('/x-data=\'workflowStageForm\((.*?), ' . $stage->id . '\)\'/s', $response->getContent(), $matches);
        $this->assertNotEmpty($matches);
        $decoded = json_decode($matches[1], true);
        $this->assertSame('Manager\'s "Special" Review', $decoded[$group->id][0]['name']);
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

    public function test_update_syncs_display_order_of_other_group_members(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
            'display_order' => 2,
        ]);
        $sibling = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
            'display_order' => 2,
        ]);
        $role = Role::create(['name' => 'approver_update_sync', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)
            ->put(route('workflow-templates.stages.update', [$template, $stage]), [
                'name' => $stage->name,
                'display_order' => 7,
                'parallel_group_id' => $group->id,
                'role_ids' => [$role->id],
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workflow_stages', ['id' => $sibling->id, 'display_order' => 7]);
    }

    // ── Store forks a new version when active instances exist ────────────────

    public function test_store_forks_new_version_when_template_has_active_instances(): void
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
                'name' => 'New Stage',
                'display_order' => 1,
                'role_ids' => [$role->id],
            ]);

        $this->assertDatabaseCount('workflow_templates', 2);
        $draft = WorkflowTemplate::where('template_group_id', $template->template_group_id)
            ->where('status', 'draft')->firstOrFail();
        $this->assertNotSame($template->id, $draft->id);
        $this->assertSame(2, $draft->version);
        $response->assertRedirect(route('workflow-templates.show', $draft));
        $this->assertDatabaseHas('workflow_stages', [
            'workflow_template_id' => $draft->id,
            'name' => 'New Stage',
        ]);
        $this->assertDatabaseMissing('workflow_stages', [
            'workflow_template_id' => $template->id,
            'name' => 'New Stage',
        ]);
        // The original stays the live, is_current version until the draft is explicitly published.
        $template->refresh();
        $this->assertTrue($template->is_current);
        $this->assertFalse($draft->is_current);
    }

    public function test_store_edits_in_place_when_no_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $role = Role::create(['name' => 'approver_no_lock', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.stages.store', $template), [
                'name' => 'New Stage',
                'display_order' => 1,
                'role_ids' => [$role->id],
            ]);

        $this->assertDatabaseCount('workflow_templates', 1);
        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_stages', [
            'workflow_template_id' => $template->id,
            'name' => 'New Stage',
        ]);
    }

    // ── Update forks a new version when active instances exist ───────────────

    public function test_update_forks_new_version_when_template_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'name' => 'Original Stage']);
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
                'name' => 'Renamed Stage',
                'display_order' => 1,
                'role_ids' => [$role->id],
            ]);

        $draft = WorkflowTemplate::where('template_group_id', $template->template_group_id)
            ->where('status', 'draft')->firstOrFail();
        $response->assertRedirect(route('workflow-templates.show', $draft));
        $this->assertDatabaseHas('workflow_stages', [
            'workflow_template_id' => $draft->id,
            'name' => 'Renamed Stage',
        ]);
        // The original stage on the still-live version is untouched — in-flight instances still see it.
        $this->assertDatabaseHas('workflow_stages', [
            'id' => $stage->id,
            'workflow_template_id' => $template->id,
            'name' => 'Original Stage',
        ]);
        $this->assertTrue($template->fresh()->is_current);
    }

    public function test_update_edits_in_place_when_no_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $role = Role::create(['name' => 'update_no_lock_role', 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)
            ->put(route('workflow-templates.stages.update', [$template, $stage]), [
                'name' => 'Renamed In Place',
                'display_order' => 1,
                'role_ids' => [$role->id],
            ]);

        $this->assertDatabaseCount('workflow_templates', 1);
        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_stages', ['id' => $stage->id, 'name' => 'Renamed In Place']);
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

    public function test_destroy_forks_new_version_when_template_has_active_instances(): void
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

        $draft = WorkflowTemplate::where('template_group_id', $template->template_group_id)
            ->where('status', 'draft')->firstOrFail();
        $response->assertRedirect(route('workflow-templates.show', $draft));
        // The original stage on the still-live version still exists — in-flight instances still see it.
        $this->assertDatabaseHas('workflow_stages', ['id' => $stage->id, 'workflow_template_id' => $template->id]);
        $this->assertDatabaseMissing('workflow_stages', ['workflow_template_id' => $draft->id, 'name' => $stage->name]);
    }

    // ── Draft/publish gate ────────────────────────────────────────────────────

    public function test_repeated_structural_edits_during_active_instances_reuse_a_single_draft(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $role = Role::create(['name' => 'draft_reuse_role', 'guard_name' => 'web']);
        $subject = PaymentRequest::factory()->inWorkflow()->create();
        WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $subject->id,
            'status' => 'in_progress',
        ]);

        // First structural edit forks a draft.
        $this->actingAs($this->user)->post(route('workflow-templates.stages.store', $template), [
            'name' => 'Stage One',
            'display_order' => 1,
            'role_ids' => [$role->id],
        ]);

        $draft = WorkflowTemplate::where('template_group_id', $template->template_group_id)
            ->where('status', 'draft')->firstOrFail();

        // A second structural edit made directly on the draft applies in place — no second fork.
        $this->actingAs($this->user)->post(route('workflow-templates.stages.store', $draft), [
            'name' => 'Stage Two',
            'display_order' => 2,
            'role_ids' => [$role->id],
        ]);

        $this->assertDatabaseCount('workflow_templates', 2);
        $this->assertDatabaseHas('workflow_stages', ['workflow_template_id' => $draft->id, 'name' => 'Stage One']);
        $this->assertDatabaseHas('workflow_stages', ['workflow_template_id' => $draft->id, 'name' => 'Stage Two']);
    }

    public function test_structural_edit_against_stale_original_redirects_to_existing_draft_instead_of_forking_again(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $role = Role::create(['name' => 'stale_page_role', 'guard_name' => 'web']);
        $subject = PaymentRequest::factory()->inWorkflow()->create();
        WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $subject->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)->post(route('workflow-templates.stages.store', $template), [
            'name' => 'Stage One',
            'display_order' => 1,
            'role_ids' => [$role->id],
        ]);
        $draft = WorkflowTemplate::where('template_group_id', $template->template_group_id)
            ->where('status', 'draft')->firstOrFail();

        // A second new payment request now attaches to the still-current original, giving it
        // active instances again. A stale request against the original (e.g. a bookmarked page)
        // must redirect to the existing draft instead of creating a second one.
        $response = $this->actingAs($this->user)->post(route('workflow-templates.stages.store', $template), [
            'name' => 'Should Not Be Created',
            'display_order' => 2,
            'role_ids' => [$role->id],
        ]);

        $response->assertRedirect(route('workflow-templates.show', $draft));
        $response->assertSessionHas('warning');
        $this->assertDatabaseCount('workflow_templates', 2);
        $this->assertDatabaseMissing('workflow_stages', ['name' => 'Should Not Be Created']);
    }
}
