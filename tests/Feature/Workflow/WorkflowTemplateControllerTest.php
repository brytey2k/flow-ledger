<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

use App\Models\Tenant\Branch;
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
        foreach (['advance', 'expense', 'retirement'] as $type) {
            $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
                'name' => "Template {$type}",
                'type' => $type,
            ]);

            $response->assertSessionHasNoErrors();
        }

        $this->assertDatabaseCount('workflow_templates', 3);
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

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_edit_form_returns_ok_with_template(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)->get(route('workflow-templates.edit', $template));

        $response->assertOk();
        $response->assertViewHas('workflowTemplate');
    }

    public function test_edit_form_passes_branches_to_view(): void
    {
        $template = WorkflowTemplate::factory()->create();
        Branch::factory()->count(2)->create();

        $response = $this->actingAs($this->user)->get(route('workflow-templates.edit', $template));

        $response->assertOk();
        $response->assertViewHas('branches');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_user_can_update_workflow_template(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
            'name' => 'New Name',
            'type' => 'expense',
        ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_templates', ['id' => $template->id, 'name' => 'New Name', 'type' => 'expense']);
    }

    public function test_update_requires_valid_type(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
            'name' => 'Test',
            'type' => 'bad_type',
        ]);

        $response->assertSessionHasErrors('type');
    }

    public function test_user_can_update_template_to_add_branch(): void
    {
        $branch = Branch::factory()->create();
        $template = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);

        $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
            'name' => $template->name,
            'type' => 'advance',
            'branch_id' => $branch->id,
        ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_templates', ['id' => $template->id, 'branch_id' => $branch->id]);
    }

    public function test_user_can_update_template_to_remove_branch(): void
    {
        $branch = Branch::factory()->create();
        $template = WorkflowTemplate::factory()->advance()->create(['branch_id' => $branch->id]);

        $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
            'name' => $template->name,
            'type' => 'advance',
        ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_templates', ['id' => $template->id, 'branch_id' => null]);
    }

    public function test_update_is_blocked_when_template_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        \App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);
        \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => \App\Models\Tenant\PaymentRequest::class,
            'workflowable_id' => \App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
            'name' => 'Changed Name',
            'type' => 'advance',
        ]);

        $response->assertRedirect()->assertSessionHas('error');
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
    }
}
