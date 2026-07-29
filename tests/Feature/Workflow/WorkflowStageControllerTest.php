<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Role;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;

test('guest is redirected from create form', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->get(route('workflow-templates.stages.create', $template));

    $response->assertRedirect(route('login'));
});
test('user without edit permission cannot access create form', function () {
    $template = WorkflowTemplate::factory()->create();
    $this->role->revokePermissionTo('edit workflow template');

    $response = $this->actingAs($this->user)
        ->get(route('workflow-templates.stages.create', $template));

    $response->assertForbidden();
});
test('user without edit permission cannot store stage', function () {
    $template = WorkflowTemplate::factory()->create();
    $this->role->revokePermissionTo('edit workflow template');

    $response = $this->actingAs($this->user)
        ->post(route('workflow-templates.stages.store', $template), []);

    $response->assertForbidden();
});
test('user without edit permission cannot delete stage', function () {
    $template = WorkflowTemplate::factory()->create();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $this->role->revokePermissionTo('edit workflow template');

    $response = $this->actingAs($this->user)
        ->delete(route('workflow-templates.stages.destroy', [$template, $stage]));

    $response->assertForbidden();
});
test('create form returns ok with roles and parallel groups', function () {
    $template = WorkflowTemplate::factory()->create();
    WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

    $response = $this->actingAs($this->user)
        ->get(route('workflow-templates.stages.create', $template));

    $response->assertOk();
    $response->assertViewHas('workflowTemplate');
    $response->assertViewHas('roles');
    $response->assertViewHas('parallelGroups');
});
test('create form exposes existing group members for client side sync check', function () {
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
});
test('create page renders stage names with quotes safely in alpine data', function () {
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
    expect($matches)->not->toBeEmpty();
    $decoded = json_decode($matches[1], true);
    expect($decoded[$group->id][0]['name'])->toBe('Manager\'s "Special" Review');
});
test('user can create stage with roles', function () {
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
});
test('store requires name', function () {
    $template = WorkflowTemplate::factory()->create();
    $role = Role::create(['name' => 'approver_b', 'guard_name' => 'web']);

    $response = $this->actingAs($this->user)
        ->post(route('workflow-templates.stages.store', $template), [
            'display_order' => 1,
            'role_ids' => [$role->id],
        ]);

    $response->assertSessionHasErrors('name');
});
test('store requires role ids', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->actingAs($this->user)
        ->post(route('workflow-templates.stages.store', $template), [
            'name' => 'Stage',
            'display_order' => 1,
            'role_ids' => [],
        ]);

    $response->assertSessionHasErrors('role_ids');
});
test('store requires display order', function () {
    $template = WorkflowTemplate::factory()->create();
    $role = Role::create(['name' => 'approver_c', 'guard_name' => 'web']);

    $response = $this->actingAs($this->user)
        ->post(route('workflow-templates.stages.store', $template), [
            'name' => 'Stage',
            'role_ids' => [$role->id],
        ]);

    $response->assertSessionHasErrors('display_order');
});
test('store accepts skip below amount', function () {
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
});
test('store accepts parallel group id', function () {
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
});
test('store syncs display order of existing group members', function () {
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
});
test('edit form returns ok with stage data', function () {
    $template = WorkflowTemplate::factory()->create();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

    $response = $this->actingAs($this->user)
        ->get(route('workflow-templates.stages.edit', [$template, $stage]));

    $response->assertOk();
    $response->assertViewHas('workflowTemplate');
    $response->assertViewHas('workflowStage');
    $response->assertViewHas('roles');
});
test('edit form exposes parallel group stages for client side sync check', function () {
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
});
test('edit page renders stage names with quotes safely in alpine data', function () {
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
    expect($matches)->not->toBeEmpty();
    $decoded = json_decode($matches[1], true);
    expect($decoded[$group->id][0]['name'])->toBe('Manager\'s "Special" Review');
});
test('user can update stage and sync roles', function () {
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
});
test('update syncs display order of other group members', function () {
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
});
test('store forks new version when template has active instances', function () {
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
    expect($draft->version)->toBe(2);
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
    expect($template->is_current)->toBeTrue();
    expect($draft->is_current)->toBeFalse();
});
test('store edits in place when no active instances', function () {
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
});
test('update forks new version when template has active instances', function () {
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
    expect($template->fresh()->is_current)->toBeTrue();
});
test('update edits in place when no active instances', function () {
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
});
test('user can delete stage', function () {
    $template = WorkflowTemplate::factory()->create();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

    $response = $this->actingAs($this->user)
        ->delete(route('workflow-templates.stages.destroy', [$template, $stage]));

    $response->assertRedirect(route('workflow-templates.show', $template));
    $this->assertDatabaseMissing('workflow_stages', ['id' => $stage->id]);
});
test('destroy forks new version when template has active instances', function () {
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
});
test('repeated structural edits during active instances reuse a single draft', function () {
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
});
test('structural edit against stale original redirects to existing draft instead of forking again', function () {
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
});
