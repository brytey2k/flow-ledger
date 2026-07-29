<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;

function makeServiceForPaymentRequestService(): PaymentRequestService
{
    return app(PaymentRequestService::class);
}
test('cancel without active instance sets status to cancelled', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    makeServiceForPaymentRequestService()->cancel($request, $this->user);

    $this->assertDatabaseHas('payment_requests', [
        'id' => $request->id,
        'status' => 'cancelled',
    ]);
});
test('cancel logs activity', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    makeServiceForPaymentRequestService()->cancel($request, $this->user);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => PaymentRequest::class,
        'subject_id' => $request->id,
        'event' => 'request.cancelled',
    ]);
});
test('cancel with active instance cancels instance and stages', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stageDef = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);

    $request = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);

    $instance = WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $request->id,
        'status' => 'in_progress',
    ]);

    WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDef->id,
        'status' => 'pending',
        'started_at' => null,
    ]);

    makeServiceForPaymentRequestService()->cancel($request, $this->user);

    $this->assertDatabaseHas('payment_requests', ['id' => $request->id, 'status' => 'cancelled']);
    $this->assertDatabaseHas('workflow_instances', ['id' => $instance->id, 'status' => 'cancelled']);
    $this->assertDatabaseHas('workflow_instance_stages', [
        'workflow_instance_id' => $instance->id,
        'status' => 'cancelled',
    ]);
});
test('cancel with active instance cancels both pending and active stages', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stageDef = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);

    $request = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);

    $instance = WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $request->id,
        'status' => 'in_progress',
    ]);

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

    makeServiceForPaymentRequestService()->cancel($request, $this->user);

    $cancelledCount = WorkflowInstanceStage::where('workflow_instance_id', $instance->id)
        ->where('status', 'cancelled')
        ->count();

    expect($cancelledCount)->toEqual(2);
});
test('submit uses branch specific template when available', function () {
    $branch = Branch::factory()->create();
    $masterTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);
    $branchTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => $branch->id]);

    WorkflowStage::factory()->create(['workflow_template_id' => $branchTemplate->id, 'display_order' => 1]);

    $request = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'branch_id' => $branch->id,
    ]);

    makeServiceForPaymentRequestService()->submit($request, $this->user);

    $instance = WorkflowInstance::where('workflowable_id', $request->id)
        ->where('workflowable_type', PaymentRequest::class)
        ->firstOrFail();

    expect($instance->workflow_template_id)->toEqual($branchTemplate->id);
    $this->assertNotEquals($masterTemplate->id, $instance->workflow_template_id);
});
test('submit falls back to master template when no branch template', function () {
    $branch = Branch::factory()->create();
    $masterTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);

    WorkflowStage::factory()->create(['workflow_template_id' => $masterTemplate->id, 'display_order' => 1]);

    $request = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'branch_id' => $branch->id,
    ]);

    makeServiceForPaymentRequestService()->submit($request, $this->user);

    $instance = WorkflowInstance::where('workflowable_id', $request->id)
        ->where('workflowable_type', PaymentRequest::class)
        ->firstOrFail();

    expect($instance->workflow_template_id)->toEqual($masterTemplate->id);
});
test('disburse sets status to disbursed', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'total_amount' => 100.0]);
    $dto = new App\DTOs\Tenant\DisbursePaymentRequestDto(
        method: App\Enums\Tenant\PaymentMethod::Cash,
        reference: null,
    );

    makeServiceForPaymentRequestService()->disburse($request, $dto, $this->user);

    $this->assertDatabaseHas('payment_requests', [
        'id' => $request->id,
        'status' => 'disbursed',
    ]);
});
test('disburse records disbursed at and disbursed by', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'total_amount' => 100.0]);
    $dto = new App\DTOs\Tenant\DisbursePaymentRequestDto(
        method: App\Enums\Tenant\PaymentMethod::Cash,
        reference: 'REF-123',
    );

    makeServiceForPaymentRequestService()->disburse($request, $dto, $this->user);

    $request->refresh();
    expect($request->disbursed_at)->not->toBeNull();
    expect($request->disbursed_by_user_id)->toEqual($this->user->id);
    expect($request->disbursement_method->value)->toEqual(App\Enums\Tenant\PaymentMethod::Cash->value);
    expect($request->disbursement_reference)->toEqual('REF-123');
});
test('disburse creates cashbook entry', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'total_amount' => 200.0]);
    $dto = new App\DTOs\Tenant\DisbursePaymentRequestDto(
        method: App\Enums\Tenant\PaymentMethod::Cash,
        reference: null,
    );

    makeServiceForPaymentRequestService()->disburse($request, $dto, $this->user);

    $this->assertDatabaseHas('cashbook_entries', [
        'sourceable_type' => PaymentRequest::class,
        'sourceable_id' => $request->id,
        'type' => 'credit',
    ]);
});
test('disburse logs activity', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'total_amount' => 100.0]);
    $dto = new App\DTOs\Tenant\DisbursePaymentRequestDto(
        method: App\Enums\Tenant\PaymentMethod::Cash,
        reference: null,
    );

    makeServiceForPaymentRequestService()->disburse($request, $dto, $this->user);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => PaymentRequest::class,
        'subject_id' => $request->id,
        'event' => 'request.disbursed',
    ]);
});
test('decline sets status to denied', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    makeServiceForPaymentRequestService()->decline($request, $this->user);

    $this->assertDatabaseHas('payment_requests', [
        'id' => $request->id,
        'status' => 'denied',
    ]);
});
test('decline logs activity', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    makeServiceForPaymentRequestService()->decline($request, $this->user);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => PaymentRequest::class,
        'subject_id' => $request->id,
        'event' => 'request.denied',
    ]);
});
test('decline cancels active workflow stages', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stageDef = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);

    $request = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);
    $instance = WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $request->id,
        'status' => 'in_progress',
    ]);
    WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDef->id,
        'status' => 'active',
        'started_at' => now(),
    ]);

    makeServiceForPaymentRequestService()->decline($request, $this->user);

    $this->assertDatabaseHas('workflow_instances', ['id' => $instance->id, 'status' => 'cancelled']);
    $this->assertDatabaseHas('workflow_instance_stages', [
        'workflow_instance_id' => $instance->id,
        'status' => 'cancelled',
    ]);
});
test('decline without workflow instance sets status to denied', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    makeServiceForPaymentRequestService()->decline($request, $this->user);

    $this->assertDatabaseHas('payment_requests', ['id' => $request->id, 'status' => 'denied']);
});
