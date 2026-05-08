<?php

declare(strict_types=1);

namespace Tests\Feature\Workflow;

use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowTemplate;
use Tests\TenantAppTestCase;

class WorkflowParallelGroupControllerTest extends TenantAppTestCase
{
    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_store(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->post(route('workflow-templates.parallel-groups.store', $template), []);

        $response->assertRedirect(route('login'));
    }

    public function test_user_without_edit_permission_cannot_store_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $this->role->revokePermissionTo('edit workflow template');

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.parallel-groups.store', $template), [
                'name' => 'Group A',
                'require_all' => true,
            ]);

        $response->assertForbidden();
    }

    public function test_user_without_edit_permission_cannot_delete_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        $this->role->revokePermissionTo('edit workflow template');

        $response = $this->actingAs($this->user)
            ->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]));

        $response->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_user_can_create_parallel_group_with_require_all(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.parallel-groups.store', $template), [
                'name' => 'Finance & Compliance',
                'require_all' => true,
            ]);

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseHas('workflow_parallel_groups', [
            'workflow_template_id' => $template->id,
            'name' => 'Finance & Compliance',
            'require_all' => true,
        ]);
    }

    public function test_user_can_create_parallel_group_with_require_any(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.parallel-groups.store', $template), [
                'name' => 'Either Approver',
                'require_all' => false,
            ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('workflow_parallel_groups', [
            'name' => 'Either Approver',
            'require_all' => false,
        ]);
    }

    public function test_store_requires_name(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.parallel-groups.store', $template), [
                'require_all' => true,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_requires_require_all(): void
    {
        $template = WorkflowTemplate::factory()->create();

        $response = $this->actingAs($this->user)
            ->post(route('workflow-templates.parallel-groups.store', $template), [
                'name' => 'Group',
            ]);

        $response->assertSessionHasErrors('require_all');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_parallel_group(): void
    {
        $template = WorkflowTemplate::factory()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

        $response = $this->actingAs($this->user)
            ->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]));

        $response->assertRedirect(route('workflow-templates.show', $template));
        $this->assertDatabaseMissing('workflow_parallel_groups', ['id' => $group->id]);
    }
}
