<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowTemplate;

test('guest is redirected from store', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->post(route('workflow-templates.parallel-groups.store', $template), []);

    $response->assertRedirect(route('login'));
});
test('user without edit permission cannot store parallel group', function () {
    $template = WorkflowTemplate::factory()->create();
    $this->role->revokePermissionTo('edit workflow template');

    $response = $this->actingAs($this->user)
        ->post(route('workflow-templates.parallel-groups.store', $template), [
            'name' => 'Group A',
            'require_all' => true,
        ]);

    $response->assertForbidden();
});
test('user without edit permission cannot delete parallel group', function () {
    $template = WorkflowTemplate::factory()->create();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
    $this->role->revokePermissionTo('edit workflow template');

    $response = $this->actingAs($this->user)
        ->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]));

    $response->assertForbidden();
});
test('user can create parallel group with require all', function () {
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
});
test('user can create parallel group with require any', function () {
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
});
test('store requires name', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->actingAs($this->user)
        ->post(route('workflow-templates.parallel-groups.store', $template), [
            'require_all' => true,
        ]);

    $response->assertSessionHasErrors('name');
});
test('store requires require all', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->actingAs($this->user)
        ->post(route('workflow-templates.parallel-groups.store', $template), [
            'name' => 'Group',
        ]);

    $response->assertSessionHasErrors('require_all');
});
test('user can delete parallel group', function () {
    $template = WorkflowTemplate::factory()->create();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

    $response = $this->actingAs($this->user)
        ->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]));

    $response->assertRedirect(route('workflow-templates.show', $template));
    $this->assertDatabaseMissing('workflow_parallel_groups', ['id' => $group->id]);
});
