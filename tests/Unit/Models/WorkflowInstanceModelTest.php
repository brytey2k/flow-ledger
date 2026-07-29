<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;

function makeInstance(): WorkflowInstance
{
    $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
    $pr = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);

    return WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $pr->id,
        'status' => 'in_progress',
    ]);
}
test('sent back stage returns null when no sent back stage', function () {
    $instance = makeInstance();

    expect($instance->sentBackStage())->toBeNull();
});
test('sent back stage returns the stage when set', function () {
    $instance = makeInstance();
    $template = WorkflowTemplate::factory()->create(['type' => 'expense']);
    $stageDef = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

    $stage = WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDef->id,
        'status' => 'sent_back',
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    $instance->update(['sent_back_to_stage_id' => $stage->id]);
    $instance->refresh();

    $sentBack = $instance->sentBackStage();
    expect($sentBack)->not->toBeNull();
    expect($sentBack->id)->toEqual($stage->id);
});
test('is in progress returns true when status is in progress', function () {
    $instance = makeInstance();

    expect($instance->isInProgress())->toBeTrue();
});
test('is in progress returns false when status is not in progress', function () {
    $instance = makeInstance();
    $instance->update(['status' => 'completed']);

    expect($instance->isInProgress())->toBeFalse();
});
test('active instance stages relation filters by active status', function () {
    $instance = makeInstance();
    $template = WorkflowTemplate::factory()->create(['type' => 'expense']);
    $stageDef = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

    WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDef->id,
        'status' => 'active',
        'started_at' => now(),
    ]);

    WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDef->id,
        'status' => 'pending',
    ]);

    expect($instance->activeInstanceStages)->toHaveCount(1);
});
