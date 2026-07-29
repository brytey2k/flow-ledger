<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;

test('guest is redirected from index', function () {
    $response = $this->get(route('workflow-templates.index'));

    $response->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $response = $this->get(route('workflow-templates.create'));

    $response->assertRedirect(route('login'));
});
test('user without permission cannot access index', function () {
    $this->role->revokePermissionTo('access workflow templates');

    $response = $this->actingAs($this->user)->get(route('workflow-templates.index'));

    $response->assertForbidden();
});
test('user without create permission cannot access create form', function () {
    $this->role->revokePermissionTo('create workflow template');

    $response = $this->actingAs($this->user)->get(route('workflow-templates.create'));

    $response->assertForbidden();
});
test('user without edit permission cannot access edit form', function () {
    $template = WorkflowTemplate::factory()->create();
    $this->role->revokePermissionTo('edit workflow template');

    $response = $this->actingAs($this->user)->get(route('workflow-templates.edit', $template));

    $response->assertForbidden();
});
test('user without delete permission cannot delete template', function () {
    $template = WorkflowTemplate::factory()->create();
    $this->role->revokePermissionTo('delete workflow template');

    $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $template));

    $response->assertForbidden();
});
test('index returns ok and lists templates', function () {
    WorkflowTemplate::factory()->count(3)->create();

    $response = $this->actingAs($this->user)->get(route('workflow-templates.index'));

    $response->assertOk();
    $response->assertViewHas('templates');
    expect($response->viewData('templates'))->toHaveCount(3);
});
test('create form returns ok', function () {
    $response = $this->actingAs($this->user)->get(route('workflow-templates.create'));

    $response->assertOk();
});
test('create form passes branches to view', function () {
    Branch::factory()->count(3)->create();

    $response = $this->actingAs($this->user)->get(route('workflow-templates.create'));

    $response->assertOk();
    $response->assertViewHas('branches');
});
test('user can create workflow template', function () {
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
});
test('store requires name', function () {
    $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
        'type' => 'advance',
    ]);

    $response->assertSessionHasErrors('name');
});
test('store requires valid type', function () {
    $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
        'name' => 'Test',
        'type' => 'invalid_type',
    ]);

    $response->assertSessionHasErrors('type');
});
test('store rejects missing type', function () {
    $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
        'name' => 'Test',
    ]);

    $response->assertSessionHasErrors('type');
});
test('store accepts all valid types', function () {
    foreach ([App\Enums\Tenant\PaymentRequestType::Advance->value, App\Enums\Tenant\PaymentRequestType::Retirement->value] as $type) {
        $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
            'name' => "Template {$type}",
            'type' => $type,
        ]);

        $response->assertSessionHasNoErrors();
    }

    // store no longer accepts 'expense' templates, so two templates are created here
    $this->assertDatabaseCount('workflow_templates', 2);
});
test('user can create branch specific template', function () {
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
});
test('store rejects nonexistent branch id', function () {
    $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
        'name' => 'Test Template',
        'type' => 'advance',
        'branch_id' => 999999,
    ]);

    $response->assertSessionHasErrors('branch_id');
});
test('store without branch id creates master template', function () {
    $response = $this->actingAs($this->user)->post(route('workflow-templates.store'), [
        'name' => 'Master Advance Approval',
        'type' => 'advance',
    ]);

    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('workflow_templates', [
        'name' => 'Master Advance Approval',
        'branch_id' => null,
    ]);
});
test('show returns ok with template data', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->actingAs($this->user)->get(route('workflow-templates.show', $template));

    $response->assertOk();
    $response->assertViewHas('workflowTemplate');
    expect($response->viewData('workflowTemplate')->is($template))->toBeTrue();
});
test('show renders help tooltips for stage columns', function () {
    $template = WorkflowTemplate::factory()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

    $response = $this->actingAs($this->user)->get(route('workflow-templates.show', $template));

    $response->assertOk();
    $response->assertSee('data-kt-tooltip-content', false);
    $response->assertSee(__('workflows.show.column_tips.order'));
    $response->assertSee(__('workflows.show.column_tips.parallel_group'));
    $response->assertSee(__('workflows.show.column_tips.skip_below'));
    $response->assertSee(__('workflows.show.column_tips.approver_scope'));
});
test('edit form returns ok with template', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->actingAs($this->user)->get(route('workflow-templates.edit', $template));

    $response->assertOk();
    $response->assertViewHas('workflowTemplate');
});
test('edit form does not pass branches to view', function () {
    $template = WorkflowTemplate::factory()->create();
    Branch::factory()->count(2)->create();

    $response = $this->actingAs($this->user)->get(route('workflow-templates.edit', $template));

    $response->assertOk();
    $response->assertViewMissing('branches');
});
test('user can update template name only', function () {
    $template = WorkflowTemplate::factory()->advance()->create(['name' => 'Old Name']);

    $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
        'name' => 'New Name',
    ]);

    $response->assertRedirect(route('workflow-templates.show', $template));
    $this->assertDatabaseHas('workflow_templates', ['id' => $template->id, 'name' => 'New Name']);
});
test('update ignores submitted type and branch id', function () {
    $branch = Branch::factory()->create();
    $template = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);

    $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
        'name' => 'New Name',
        'type' => App\Enums\Tenant\PaymentRequestType::Retirement->value,
        'branch_id' => $branch->id,
    ]);

    $response->assertRedirect(route('workflow-templates.show', $template));
    $this->assertDatabaseHas('workflow_templates', [
        'id' => $template->id,
        'name' => 'New Name',
        'type' => 'advance',
        'branch_id' => null,
    ]);
});
test('name update is allowed even when template has active instances', function () {
    $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
    App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => App\Models\Tenant\PaymentRequest::class,
        'workflowable_id' => App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
        'status' => 'in_progress',
    ]);

    $response = $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
        'name' => 'Changed Name',
    ]);

    $response->assertRedirect(route('workflow-templates.show', $template));
    $response->assertSessionHasNoErrors();
    $this->assertDatabaseHas('workflow_templates', ['id' => $template->id, 'name' => 'Changed Name']);
});
test('name update does not create new version', function () {
    $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
    App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => App\Models\Tenant\PaymentRequest::class,
        'workflowable_id' => App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
        'status' => 'in_progress',
    ]);

    $this->actingAs($this->user)->put(route('workflow-templates.update', $template), [
        'name' => 'Changed Name',
    ]);

    $this->assertDatabaseCount('workflow_templates', 1);
    $template->refresh();
    expect($template->version)->toBe(1);
    expect($template->is_current)->toBeTrue();
});
test('user can delete workflow template', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $template));

    $response->assertRedirect(route('workflow-templates.index'));
    $this->assertDatabaseMissing('workflow_templates', ['id' => $template->id]);
});
test('destroy is blocked when template has active instances', function () {
    $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
    App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => App\Models\Tenant\PaymentRequest::class,
        'workflowable_id' => App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
        'status' => 'in_progress',
    ]);

    $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $template));

    $response->assertRedirect()->assertSessionHas('error');
    $this->assertDatabaseHas('workflow_templates', ['id' => $template->id]);
});
test('destroy is blocked when any version in family has active instances', function () {
    $superseded = WorkflowTemplate::factory()->create(['type' => 'advance', 'version' => 1, 'is_current' => false]);
    $current = WorkflowTemplate::factory()->create([
        'type' => 'advance',
        'template_group_id' => $superseded->template_group_id,
        'version' => 2,
        'is_current' => true,
    ]);
    App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $superseded->id,
        'workflowable_type' => App\Models\Tenant\PaymentRequest::class,
        'workflowable_id' => App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
        'status' => 'in_progress',
    ]);

    $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $current));

    $response->assertRedirect()->assertSessionHas('error');
    $this->assertDatabaseHas('workflow_templates', ['id' => $current->id]);
    $this->assertDatabaseHas('workflow_templates', ['id' => $superseded->id]);
});
test('destroy deletes all versions in family when none have active instances', function () {
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
});
test('destroy blocked with friendly message when version has only terminal instances', function () {
    $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
    App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => App\Models\Tenant\PaymentRequest::class,
        'workflowable_id' => App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'approved'])->id,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($this->user)->delete(route('workflow-templates.destroy', $template));

    $response->assertRedirect(route('workflow-templates.show', $template));
    $response->assertSessionHas('error', __('flash.workflows.template_has_history'));
    $this->assertDatabaseHas('workflow_templates', ['id' => $template->id]);
});
test('guest is redirected from versions', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->get(route('workflow-templates.versions', $template));

    $response->assertRedirect(route('login'));
});
test('user without permission cannot access versions', function () {
    $template = WorkflowTemplate::factory()->create();
    $this->role->revokePermissionTo('access workflow templates');

    $response = $this->actingAs($this->user)->get(route('workflow-templates.versions', $template));

    $response->assertForbidden();
});
test('versions returns ok and lists all versions in family', function () {
    $superseded = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => false]);
    $current = WorkflowTemplate::factory()->create([
        'template_group_id' => $superseded->template_group_id,
        'version' => 2,
        'is_current' => true,
    ]);

    $response = $this->actingAs($this->user)->get(route('workflow-templates.versions', $current));

    $response->assertOk();
    $response->assertViewHas('versions');
    expect($response->viewData('versions'))->toHaveCount(2);
});
test('versions shows correct current flag per row', function () {
    $superseded = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => false]);
    $current = WorkflowTemplate::factory()->create([
        'template_group_id' => $superseded->template_group_id,
        'version' => 2,
        'is_current' => true,
    ]);

    $response = $this->actingAs($this->user)->get(route('workflow-templates.versions', $current));

    $versions = $response->viewData('versions')->keyBy('id');
    expect($versions[$superseded->id]->is_current)->toBeFalse();
    expect($versions[$current->id]->is_current)->toBeTrue();
});
test('user can publish a draft', function () {
    $current = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => true]);
    $draft = WorkflowTemplate::factory()->draft()->create([
        'template_group_id' => $current->template_group_id,
        'version' => 2,
    ]);

    $response = $this->actingAs($this->user)->post(route('workflow-templates.publish', $draft));

    $response->assertRedirect(route('workflow-templates.show', $draft));
    $response->assertSessionHas('success');
    $draft->refresh();
    expect($draft->is_current)->toBeTrue();
    expect($draft->status)->toBe('published');
    expect($current->fresh()->is_current)->toBeFalse();
});
test('publish rejects a non draft template', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->actingAs($this->user)->post(route('workflow-templates.publish', $template));

    $response->assertStatus(422);
    expect($template->fresh()->is_current)->toBeTrue();
});
test('user without edit permission cannot publish', function () {
    $draft = WorkflowTemplate::factory()->draft()->create();
    $this->role->revokePermissionTo('edit workflow template');

    $response = $this->actingAs($this->user)->post(route('workflow-templates.publish', $draft));

    $response->assertForbidden();
});
test('user can discard a draft', function () {
    $current = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => true]);
    $draft = WorkflowTemplate::factory()->draft()->create([
        'template_group_id' => $current->template_group_id,
        'version' => 2,
    ]);

    $response = $this->actingAs($this->user)->delete(route('workflow-templates.draft.discard', $draft));

    $response->assertRedirect(route('workflow-templates.show', $current));
    $response->assertSessionHas('success');
    $this->assertDatabaseMissing('workflow_templates', ['id' => $draft->id]);
});
test('discard rejects a non draft template', function () {
    $template = WorkflowTemplate::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('workflow-templates.draft.discard', $template));

    $response->assertStatus(422);
    $this->assertDatabaseHas('workflow_templates', ['id' => $template->id]);
});
test('user without edit permission cannot discard draft', function () {
    $draft = WorkflowTemplate::factory()->draft()->create();
    $this->role->revokePermissionTo('edit workflow template');

    $response = $this->actingAs($this->user)->delete(route('workflow-templates.draft.discard', $draft));

    $response->assertForbidden();
});
test('versions shows instance counts per version', function () {
    $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
    App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => App\Models\Tenant\PaymentRequest::class,
        'workflowable_id' => App\Models\Tenant\PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
        'status' => 'in_progress',
    ]);

    $response = $this->actingAs($this->user)->get(route('workflow-templates.versions', $template));

    $versions = $response->viewData('versions')->keyBy('id');
    expect($versions[$template->id]->instances_count)->toBe(1);
    expect($versions[$template->id]->active_instances_count)->toBe(1);
});
