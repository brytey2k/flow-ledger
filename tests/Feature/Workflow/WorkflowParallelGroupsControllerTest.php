<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowTemplate;

test('guest cannot store parallel group', function () {
    $template = WorkflowTemplate::factory()->create();

    $this->post(route('workflow-templates.parallel-groups.store', $template), [])
        ->assertRedirect(route('login'));
});
test('guest cannot delete parallel group', function () {
    $template = WorkflowTemplate::factory()->create();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

    $this->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]))
        ->assertRedirect(route('login'));
});
test('user without edit permission cannot store parallel group', function () {
    $template = WorkflowTemplate::factory()->create();
    $this->role->revokePermissionTo('edit workflow template');

    $this->actingAs($this->user)
        ->post(route('workflow-templates.parallel-groups.store', $template), [
            'name' => 'Finance Group',
            'require_all' => true,
        ])
        ->assertForbidden();
});
test('user without edit permission cannot destroy parallel group', function () {
    $template = WorkflowTemplate::factory()->create();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
    $this->role->revokePermissionTo('edit workflow template');

    $this->actingAs($this->user)
        ->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]))
        ->assertForbidden();
});
test('authorized user can create parallel group', function () {
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
});
test('can create parallel group with require any', function () {
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
});
test('store forks new version when template has active instances', function () {
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
            'name' => 'New Group',
            'require_all' => true,
        ]);

    $draft = WorkflowTemplate::where('template_group_id', $template->template_group_id)
        ->where('status', 'draft')->firstOrFail();
    $response->assertRedirect(route('workflow-templates.show', $draft));
    $this->assertDatabaseHas('workflow_parallel_groups', [
        'workflow_template_id' => $draft->id,
        'name' => 'New Group',
    ]);
    $this->assertDatabaseMissing('workflow_parallel_groups', [
        'workflow_template_id' => $template->id,
        'name' => 'New Group',
    ]);
    expect($template->fresh()->is_current)->toBeTrue();
});
test('authorized user can delete parallel group', function () {
    $template = WorkflowTemplate::factory()->create();
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

    $response = $this->actingAs($this->user)
        ->delete(route('workflow-templates.parallel-groups.destroy', [$template, $group]));

    $response->assertRedirect(route('workflow-templates.show', $template));
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('workflow_parallel_groups', ['id' => $group->id]);
});
test('destroy forks new version when template has active instances', function () {
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

    $draft = WorkflowTemplate::where('template_group_id', $template->template_group_id)
        ->where('status', 'draft')->firstOrFail();
    $response->assertRedirect(route('workflow-templates.show', $draft));

    // The original group on the still-live version is untouched.
    $this->assertDatabaseHas('workflow_parallel_groups', ['id' => $group->id, 'workflow_template_id' => $template->id]);
    $this->assertDatabaseMissing('workflow_parallel_groups', ['workflow_template_id' => $draft->id, 'name' => $group->name]);
});
