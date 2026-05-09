<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use App\Services\WorkflowEngineService;
use Tests\TenantAppTestCase;

class ActivityLogTest extends TenantAppTestCase
{
    public function test_creating_draft_logs_request_created_event(): void
    {
        $staff = Staff::factory()->create();
        $currency = Currency::factory()->create();
        $dto = new \App\DTOs\Tenant\CreatePaymentRequestDto(
            staffId: $staff->id,
            branchId: $this->branch->id,
            currencyId: $currency->id,
            type: 'advance',
            notes: null,
            items: [new \App\DTOs\Tenant\PaymentRequestItemDto(description: 'Test', amount: 100.0, accountCodeId: null, receiptNumber: null)],
        );
        $paymentRequest = app(PaymentRequestService::class)->createDraft($dto, $this->user);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => PaymentRequest::class,
            'subject_id' => $paymentRequest->id,
            'event' => 'request.created',
        ]);
    }

    public function test_submitting_request_logs_submitted_event(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        app(PaymentRequestService::class)->submit($paymentRequest, $this->user);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => PaymentRequest::class,
            'subject_id' => $paymentRequest->id,
            'event' => 'request.submitted',
        ]);
    }

    public function test_approving_stage_logs_stage_approved_event(): void
    {
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
    }

    public function test_full_approval_logs_request_approved_event(): void
    {
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
    }

    public function test_send_back_logs_stage_sent_back_event(): void
    {
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
    }
}
