<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;

test('template relation loads workflow template', function () {
    $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

    expect($group->template->id)->toEqual($template->id);
});
test('stages relation returns associated stages', function () {
    $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
    $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
    WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'parallel_group_id' => $group->id,
    ]);

    expect($group->stages)->toHaveCount(1);
});
test('require all casts to boolean', function () {
    $group = WorkflowParallelGroup::factory()->create(['require_all' => true]);

    expect($group->require_all)->toBeTrue();
});
