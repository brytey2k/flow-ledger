<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;

function makeStage(string $status = 'active'): WorkflowInstanceStage
{
    $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
    $stageDef = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $pr = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);
    $instance = WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $pr->id,
        'status' => 'in_progress',
    ]);

    return WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDef->id,
        'status' => $status,
        'started_at' => now(),
    ]);
}
test('is active returns true when status is active', function () {
    $stage = makeStage('active');

    expect($stage->isActive())->toBeTrue();
});
test('is active returns false when status is not active', function () {
    $stage = makeStage('approved');

    expect($stage->isActive())->toBeFalse();
});
test('is terminal returns true for approved status', function () {
    $stage = makeStage('approved');

    expect($stage->isTerminal())->toBeTrue();
});
test('is terminal returns false for active status', function () {
    $stage = makeStage('active');

    expect($stage->isTerminal())->toBeFalse();
});
test('instance relation loads workflow instance', function () {
    $stage = makeStage();

    expect($stage->instance)->toBeInstanceOf(WorkflowInstance::class);
});
test('stage relation loads workflow stage definition', function () {
    $stage = makeStage();

    expect($stage->stage)->toBeInstanceOf(WorkflowStage::class);
});
