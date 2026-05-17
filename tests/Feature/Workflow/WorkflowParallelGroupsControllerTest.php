<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowTemplate;
use Tests\TenantAppTestCase;

class WorkflowParallelGroupsControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_cannot_store_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $this->post(route('workflow-templates.parallel-groups.store', $template), [])
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_delete_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

        $this->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]))
            ->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_edit_permission_cannot_store_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $this->role->revokePermissionTo('edit workflow template');

        $this->actingAs($this->user)
            ->post(route('workflow-templates.parallel-groups.store', $template), [
                'name' => 'Finance Group',
                'require_all' => true,
            ])
            ->assertForbidden();
    }

    public function test_user_without_edit_permission_cannot_destroy_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        $this->role->revokePermissionTo('edit workflow template');

        $this->actingAs($this->user)
            ->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]))
            ->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorized_user_can_create_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.parallel-groups.store', $template), [
                'name' => 'Finance Review Group',
                'require_all' => true,
            ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('workflow_parallel_groups', [
            'workflow_template_id' => $template->id,
            'name' => 'Finance Review Group',
            'require_all' => true,
        ]);
    }

    public function test_can_create_parallel_group_with_require_any(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.parallel-groups.store', $template), [
                'name' => 'Ops Group',
                'require_all' => false,
            ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_parallel_groups', [
            'workflow_template_id' => $template->id,
            'name' => 'Ops Group',
            'require_all' => false,
        ]);
    }

    public function test_store_is_blocked_when_template_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $subject = PaymentRequest::factory()->inWorkflow()->create();
        WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $subject->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.parallel-groups.store', $template), [
                'name' => 'Blocked Group',
                'require_all' => true,
            ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('workflow_parallel_groups', [
            'name' => 'Blocked Group',
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorized_user_can_delete_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]));

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('workflow_parallel_groups', ['id' => $group->id]);
    }

    public function test_destroy_is_blocked_when_template_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        $subject = PaymentRequest::factory()->inWorkflow()->create();
        WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $subject->id,
            'status' => 'in_progress',
        ]);

        $response = $this->actingAs($this->user)
            ->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]));

        $response->assertRedirect(route('workflow-templates.show', $template));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('workflow_parallel_groups', ['id' => $group->id]);
    }
}
