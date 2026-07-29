<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use App\Services\WorkflowEngineService;

test('creating draft logs request created event', function () {
    $staff = Staff::factory()->create();
    $currency = Currency::factory()->create();
    $dto = new App\DTOs\Tenant\CreatePaymentRequestDto(
        staffId: $staff->id,
        branchId: $this->branch->id,
        currencyId: $currency->id,
        type: 'advance',
        notes: null,
        items: [new App\DTOs\Tenant\PaymentRequestItemDto(description: 'Test', amount: 100.0, costCodeId: null, receiptNumber: null)],
    );
    $paymentRequest = app(PaymentRequestService::class)->createDraft($dto, $this->user);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => PaymentRequest::class,
        'subject_id' => $paymentRequest->id,
        'event' => 'request.created',
    ]);
});
test('submitting request logs submitted event', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
    app(PaymentRequestService::class)->submit($paymentRequest, $this->user);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => PaymentRequest::class,
        'subject_id' => $paymentRequest->id,
        'event' => 'request.submitted',
    ]);
});
test('approving stage logs stage approved event', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
    app(PaymentRequestService::class)->submit($paymentRequest);

    $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();
    app(WorkflowEngineService::class)->approve($instanceStage, $this->user, 'All good');

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => PaymentRequest::class,
        'subject_id' => $paymentRequest->id,
        'event' => 'stage.approved',
    ]);
});
test('full approval logs request approved event', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
    app(PaymentRequestService::class)->submit($paymentRequest);

    $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();
    app(WorkflowEngineService::class)->approve($instanceStage, $this->user);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => PaymentRequest::class,
        'subject_id' => $paymentRequest->id,
        'event' => 'request.approved',
    ]);
});
test('send back logs stage sent back event', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
    app(PaymentRequestService::class)->submit($paymentRequest);

    $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();
    app(WorkflowEngineService::class)->sendBack($instanceStage, $this->user, 'Fix the amounts');

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => PaymentRequest::class,
        'subject_id' => $paymentRequest->id,
        'event' => 'stage.sent_back',
    ]);
});
