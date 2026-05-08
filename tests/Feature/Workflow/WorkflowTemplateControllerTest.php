<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

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

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_workflow_template(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $template));

        $response->assertRedirect(route('workflow-templates.index'));
        $this->assertDatabaseMissing('workflow_templates', ['id' => $template->id]);
    }
}
