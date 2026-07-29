<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;

test('resolve for branch returns branch specific template when exists', function () {
    $branch = Branch::factory()->create();
    $master = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);
    $branchTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => $branch->id]);

    $resolved = WorkflowTemplate::resolveForBranch('advance', $branch->id);

    expect($resolved->is($branchTemplate))->toBeTrue();
    expect($resolved->is($master))->toBeFalse();
});
test('resolve for branch falls back to master when no branch template exists', function () {
    $branch = Branch::factory()->create();
    $master = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);

    $resolved = WorkflowTemplate::resolveForBranch('advance', $branch->id);

    expect($resolved->is($master))->toBeTrue();
});
test('resolve for branch with null branch id returns master template', function () {
    $master = WorkflowTemplate::factory()->retirement()->create(['branch_id' => null]);

    $resolved = WorkflowTemplate::resolveForBranch('retirement', null);

    expect($resolved->is($master))->toBeTrue();
});
test('resolve for branch throws when no master template exists', function () {
    $this->expectException(ModelNotFoundException::class);

    WorkflowTemplate::resolveForBranch('expense', null);
});
test('resolve for branch throws when branch has no template and no master exists', function () {
    $branch = Branch::factory()->create();

    $this->expectException(ModelNotFoundException::class);

    WorkflowTemplate::resolveForBranch('advance', $branch->id);
});
test('branch relationship returns null for master template', function () {
    $template = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);

    expect($template->branch)->toBeNull();
});
test('branch relationship returns branch for branch specific template', function () {
    $branch = Branch::factory()->create();
    $template = WorkflowTemplate::factory()->advance()->create(['branch_id' => $branch->id]);

    expect($template->branch)->not->toBeNull();
    expect($template->branch->is($branch))->toBeTrue();
});
test('creating assigns a template group id when none given', function () {
    $template = WorkflowTemplate::factory()->create();

    expect($template->template_group_id)->not->toBeNull();
});
test('resolve for branch only returns current master template', function () {
    $superseded = WorkflowTemplate::factory()->advance()->create(['branch_id' => null, 'version' => 1, 'is_current' => false]);
    $current = WorkflowTemplate::factory()->advance()->create([
        'branch_id' => null,
        'template_group_id' => $superseded->template_group_id,
        'version' => 2,
        'is_current' => true,
    ]);

    $resolved = WorkflowTemplate::resolveForBranch('advance', null);

    expect($resolved->is($current))->toBeTrue();
});
test('resolve for branch only returns current branch specific template', function () {
    $branch = Branch::factory()->create();
    $superseded = WorkflowTemplate::factory()->advance()->create(['branch_id' => $branch->id, 'version' => 1, 'is_current' => false]);
    $current = WorkflowTemplate::factory()->advance()->create([
        'branch_id' => $branch->id,
        'template_group_id' => $superseded->template_group_id,
        'version' => 2,
        'is_current' => true,
    ]);

    $resolved = WorkflowTemplate::resolveForBranch('advance', $branch->id);

    expect($resolved->is($current))->toBeTrue();
});
test('has active instances across family detects active instance on superseded version', function () {
    $superseded = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => false]);
    $current = WorkflowTemplate::factory()->create([
        'template_group_id' => $superseded->template_group_id,
        'version' => 2,
        'is_current' => true,
    ]);
    WorkflowInstance::create([
        'workflow_template_id' => $superseded->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => PaymentRequest::factory()->advance()->create(['status' => 'in_workflow'])->id,
        'status' => 'in_progress',
    ]);

    expect($current->hasActiveInstancesAcrossFamily())->toBeTrue();
    expect($current->hasActiveInstances())->toBeFalse();
});
test('has active instances across family returns false when no version has active instances', function () {
    $superseded = WorkflowTemplate::factory()->create(['version' => 1, 'is_current' => false]);
    $current = WorkflowTemplate::factory()->create([
        'template_group_id' => $superseded->template_group_id,
        'version' => 2,
        'is_current' => true,
    ]);

    expect($current->hasActiveInstancesAcrossFamily())->toBeFalse();
});
