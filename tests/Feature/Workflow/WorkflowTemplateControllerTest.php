<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

use App\Models\Tenant\Branch;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Tests\TenantAppTestCase;

class WorkflowTemplateControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('workflow-templates.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $response = $this->get(route('workflow-templates.create'));

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->role->revokePermissionTo('access workflow templates');

        $response = $this->actingAs($this->user)->get(route('workflow-templates.index'));

        $response->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_access_create_form(): void
    {
        $this->role->revokePermissionTo('create workflow template');

        $response = $this->actingAs($this->user)->get(route('workflow-templates.create'));

        $response->assertForbidden();
    }

    public function test_user_without_edit_permission_cannot_access_edit_form(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $this->role->revokePermissionTo('edit workflow template');

        $response = $this->actingAs($this->user)->get(route('workflow-templates.edit', $template));

        $response->assertForbidden();
    }

    public function test_user_without_delete_permission_cannot_delete_template(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $this->role->revokePermissionTo('delete workflow template');

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $template));

        $response->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_ok_and_lists_templates(): void
    {
        WorkflowTemplate::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('workflow-templates.index'));

        $response->assertOk();
        $response->assertViewHas('templates');
        $this->assertCount(3, $response->viewData('templates'));
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_form_returns_ok(): void
    {
        $response = $this->actingAs($this->user)->get(route('workflow-templates.create'));

        $response->assertOk();
    }

    public function test_create_form_passes_branches_to_view(): void
    {
        Branch::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('workflow-templates.create'));

        $response->assertOk();
        $response->assertViewHas('branches');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_user_can_create_workflow_template(): void
    {
        $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
            'name' => 'Staff Advance Approval',
            'type' => 'advance',
        ]);

        $template = WorkflowTemplate::where('name', 'Staff Advance Approval')->firstOrFail();
        $response->assertRedirect(route('workflow-templates.show', $template));

        $this->assertDatabaseHas('workflow_templates', [
            'name' => 'Staff Advance Approval',
            'type' => 'advance',
        ]);
    }

    public function test_store_requires_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
            'type' => 'advance',
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_requires_valid_type(): void
    {
        $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
            'name' => 'Test',
            'type' => 'invalid_type',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_store_rejects_missing_type(): void
    {
        $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
            'name' => 'Test',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_store_accepts_all_valid_types(): void
    {
        foreach ([\App\Enums\Tenant\PaymentRequestType::Advance->value, \App\Enums\Tenant\PaymentRequestType::Retirement->value] as $type) {
            $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
                'name' => "Template {$type}",
                'type' => $type,
            ]);

            $response->assertSessionHasNoErrors();
        }

        // store no longer accepts 'expense' templates, so two templates are created here
        $this->assertDatabaseCount('workflow_templates', 2);
    }

    public function test_user_can_create_branch_specific_template(): void
    {
        $branch = Branch::factory()->create();

        $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
            'name' => 'Branch Advance Approval',
            'type' => 'advance',
            'branch_id' => $branch->id,
        ]);

        $template = WorkflowTemplate::where('name', 'Branch Advance Approval')->firstOrFail();
        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_templates', [
            'name' => 'Branch Advance Approval',
            'type' => 'advance',
            'branch_id' => $branch->id,
        ]);
    }

    public function test_store_rejects_nonexistent_branch_id(): void
    {
        $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
            'name' => 'Test Template',
            'type' => 'advance',
            'branch_id' => 999999,
        ]);

        $response->assertSessionHasErrors('branch_id');
    }

    public function test_store_without_branch_id_creates_master_template(): void
    {
        $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
            'name' => 'Master Advance Approval',
            'type' => 'advance',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workflow_templates', [
            'name' => 'Master Advance Approval',
            'branch_id' => null,
        ]);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_show_returns_ok_with_template_data(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)->get(route('workflow-templates.show', $template));

        $response->assertOk();
        $response->assertViewHas('workflowTemplate');
        $this->assertTrue($response->viewData('workflowTemplate')->is($template));
    }

    public function test_show_renders_help_tooltips_for_stage_columns(): void
    {
        $template = WorkflowTemplate::factory()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

        $response = $this->actingAs($this->user)->get(route('workflow-templates.show', $template));

        $response->assertOk();
        $response->assertSee('data-kt-tooltip-content', false);
        $response->assertSee(__('workflows.show.column_tips.order'));
        $response->assertSee(__('workflows.show.column_tips.parallel_group'));
        $response->assertSee(__('workflows.show.column_tips.skip_below'));
        $response->assertSee(__('workflows.show.column_tips.approver_scope'));
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_edit_form_returns_ok_with_template(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)->get(route('workflow-templates.edit', $template));

        $response->assertOk();
        $response->assertViewHas('workflowTemplate');
    }

    public function test_edit_form_does_not_pass_branches_to_view(): void
    {
        $template = WorkflowTemplate::factory()->create();
        Branch::factory()->count(2)->create();

        $response = $this->actingAs($this->user)->get(route('workflow-templates.edit', $template));

        $response->assertOk();
        $response->assertViewMissing('branches');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_template_name_only(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_templates', ['id' => $template->id, 'name' => 'New Name']);
    }

    public function test_update_ignores_submitted_type_and_branch_id(): void
    {
        $branch = Branch::factory()->create();
        $template = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);

        $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
            'name' => 'New Name',
            'type' => \App\Enums\Tenant\PaymentRequestType::Retirement->value,
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_templates', [
            'id' => $template->id,
            'name' => 'New Name',
            'type' => 'advance',
            'branch_id' => null,
        ]);
    }

    public function test_name_update_is_allowed_even_when_template_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => \App\Models\Tenant\PaymentRequest::class,
            'workflowable_id' => \App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
            'name' => 'Changed Name',
        ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workflow_templates', ['id' => $template->id, 'name' => 'Changed Name']);
    }

    public function test_name_update_does_not_create_new_version(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => \App\Models\Tenant\PaymentRequest::class,
            'workflowable_id' => \App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
            'status' => 'in_progress',
        ]);

        $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
            'name' => 'Changed Name',
        ]);

        $this->assertDatabaseCount('workflow_templates', 1);
        $template->refresh();
        $this->assertSame(1, $template->version);
        $this->assertTrue($template->is_current);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_workflow_template(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $template));

        $response->assertRedirect(route('workflow-templates.index'));
        $this->assertDatabaseMissing('workflow_templates', ['id' => $template->id]);
    }

    public function test_destroy_is_blocked_when_template_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => \App\Models\Tenant\PaymentRequest::class,
            'workflowable_id' => \App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $template));

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('workflow_templates', ['id' => $template->id]);
    }

    public function test_destroy_is_blocked_when_any_version_in_family_has_active_instances(): void
    {
        $superseded = WorkflowTemplate::factory()->create(['type' => 'advance', 'version' => 1, 'is_current' => false]);
        $current = WorkflowTemplate::factory()->create([
            'type' => 'advance',
            'template_group_id' => $superseded->template_group_id,
            'version' => 2,
            'is_current' => true,
        ]);
        \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $superseded->id,
            'workflowable_type' => \App\Models\Tenant\PaymentRequest::class,
            'workflowable_id' => \App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $current));

        $response->assertRedirect()->assertSessionHas('error');
        $this->assertDatabaseHas('workflow_templates', ['id' => $current->id]);
        $this->assertDatabaseHas('workflow_templates', ['id' => $superseded->id]);
    }

    public function test_destroy_deletes_all_versions_in_family_when_none_have_active_instances(): void
    {
        $superseded = WorkflowTemplate::factory()->create(['type' => 'advance', 'version' => 1, 'is_current' => false]);
        $current = WorkflowTemplate::factory()->create([
            'type' => 'advance',
            'template_group_id' => $superseded->template_group_id,
            'version' => 2,
            'is_current' => true,
        ]);

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $current));

        $response->assertRedirect(route('workflow-templates.index'));
        $this->assertDatabaseMissing('workflow_templates', ['id' => $current->id]);
        $this->assertDatabaseMissing('workflow_templates', ['id' => $superseded->id]);
    }

    public function test_destroy_blocked_with_friendly_message_when_version_has_only_terminal_instances(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => \App\Models\Tenant\PaymentRequest::class,
            'workflowable_id' => \App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'approved'])->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $template));

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHas('error', __('flash.workflows.template_has_history'));
        $this->assertDatabaseHas('workflow_templates', ['id' => $template->id]);
    }

    // ── Versions ──────────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_versions(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->get(route('workflow-templates.versions', $template));

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_access_versions(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $this->role->revokePermissionTo('access workflow templates');

        $response = $this->actingAs($this->user)->get(route('workflow-templates.versions', $template));

        $response->assertForbidden();
    }

    public function test_versions_returns_ok_and_lists_all_versions_in_family(): void
    {
        $superseded = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => false]);
        $current = WorkflowTemplate::factory()->create([
            'template_group_id' => $superseded->template_group_id,
            'version' => 2,
            'is_current' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('workflow-templates.versions', $current));

        $response->assertOk();
        $response->assertViewHas('versions');
        $this->assertCount(2, $response->viewData('versions'));
    }

    public function test_versions_shows_correct_current_flag_per_row(): void
    {
        $superseded = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => false]);
        $current = WorkflowTemplate::factory()->create([
            'template_group_id' => $superseded->template_group_id,
            'version' => 2,
            'is_current' => true,
        ]);

        $response = $this->actingAs($this->user)->get(route('workflow-templates.versions', $current));

        $versions = $response->viewData('versions')->keyBy('id');
        $this->assertFalse($versions[$superseded->id]->is_current);
        $this->assertTrue($versions[$current->id]->is_current);
    }

    // ── Publish ───────────────────────────────────────────────────────────────

    public function test_user_can_publish_a_draft(): void
    {
        $current = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => true]);
        $draft = WorkflowTemplate::factory()->draft()->create([
            'template_group_id' => $current->template_group_id,
            'version' => 2,
        ]);

        $response = $this->actingAs($this->user)->post(route('workflow-templates.publish', $draft));

        $response->assertRedirect(route('workflow-templates.show', $draft));
        $response->assertSessionHas('success');
        $draft->refresh();
        $this->assertTrue($draft->is_current);
        $this->assertSame('published', $draft->status);
        $this->assertFalse($current->fresh()->is_current);
    }

    public function test_publish_rejects_a_non_draft_template(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)->post(route('workflow-templates.publish', $template));

        $response->assertStatus(422);
        $this->assertTrue($template->fresh()->is_current);
    }

    public function test_user_without_edit_permission_cannot_publish(): void
    {
        $draft = WorkflowTemplate::factory()->draft()->create();
        $this->role->revokePermissionTo('edit workflow template');

        $response = $this->actingAs($this->user)->post(route('workflow-templates.publish', $draft));

        $response->assertForbidden();
    }

    // ── Discard draft ─────────────────────────────────────────────────────────

    public function test_user_can_discard_a_draft(): void
    {
        $current = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => true]);
        $draft = WorkflowTemplate::factory()->draft()->create([
            'template_group_id' => $current->template_group_id,
            'version' => 2,
        ]);

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.draft.discard', $draft));

        $response->assertRedirect(route('workflow-templates.show', $current));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('workflow_templates', ['id' => $draft->id]);
    }

    public function test_discard_rejects_a_non_draft_template(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.draft.discard', $template));

        $response->assertStatus(422);
        $this->assertDatabaseHas('workflow_templates', ['id' => $template->id]);
    }

    public function test_user_without_edit_permission_cannot_discard_draft(): void
    {
        $draft = WorkflowTemplate::factory()->draft()->create();
        $this->role->revokePermissionTo('edit workflow template');

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.draft.discard', $draft));

        $response->assertForbidden();
    }

    public function test_versions_shows_instance_counts_per_version(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => \App\Models\Tenant\PaymentRequest::class,
            'workflowable_id' => \App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)->get(route('workflow-templates.versions', $template));

        $versions = $response->viewData('versions')->keyBy('id');
        $this->assertSame(1, $versions[$template->id]->instances_count);
        $this->assertSame(1, $versions[$template->id]->active_instances_count);
    }
}
