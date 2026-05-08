<?php

declare(strict_types=1);

namespace Tests\Feature\Notifications;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Notifications\RequestApprovedNotification;
use App\Notifications\RequestDisbursedNotification;
use App\Notifications\RequestRejectedNotification;
use App\Notifications\RequestSentBackNotification;
use App\Notifications\RetirementApprovedNotification;
use App\Notifications\RetirementRequiredNotification;
use App\Notifications\StageReadyForApprovalNotification;
use App\Services\PaymentRequestService;
use App\Services\RetirementService;
use App\Services\WorkflowEngineService;
use Illuminate\Support\Facades\Notification;
use Tests\TenantAppTestCase;

class NotificationTest extends TenantAppTestCase
{
    private function makeAdvanceRequestInWorkflow(): array
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        $stage->roles()->attach($this->role->id);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        app(PaymentRequestService::class)->submit($paymentRequest, $this->user);

        $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();

        return [$paymentRequest, $instanceStage];
    }

    // ── Stage activation notifications ────────────────────────────────────────

    public function test_stage_approvers_are_notified_when_workflow_starts(): void
    {
        Notification::fake();

        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        $stage->roles()->attach($this->role->id);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        app(PaymentRequestService::class)->submit($paymentRequest, $this->user);

        Notification::assertSentTo($this->user, StageReadyForApprovalNotification::class);
    }

    public function test_next_stage_approvers_are_notified_on_advance(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage1 = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        $stage2 = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 2,
        ]);
        $stage1->roles()->attach($this->role->id);
        $stage2->roles()->attach($this->role->id);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        app(PaymentRequestService::class)->submit($paymentRequest, $this->user);

        $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();

        Notification::fake();

        app(WorkflowEngineService::class)->approve($instanceStage, $this->user);

        Notification::assertSentTo($this->user, StageReadyForApprovalNotification::class);
    }

    // ── Full approval notifications ───────────────────────────────────────────

    public function test_submitter_is_notified_when_request_is_fully_approved(): void
    {
        [$paymentRequest, $instanceStage] = $this->makeAdvanceRequestInWorkflow();

        Notification::fake();

        app(WorkflowEngineService::class)->approve($instanceStage, $this->user);

        Notification::assertSentTo($this->user, RequestApprovedNotification::class);
    }

    public function test_submitter_is_notified_when_retirement_is_fully_approved(): void
    {
        $advanceRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $advanceRequest->id,
            'status' => 'draft',
        ]);

        $template = WorkflowTemplate::factory()->retirement()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        $stage->roles()->attach($this->role->id);

        app(RetirementService::class)->submit($retirement, $this->user);

        $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();

        Notification::fake();

        app(WorkflowEngineService::class)->approve($instanceStage, $this->user);

        Notification::assertSentTo($this->user, RetirementApprovedNotification::class);
    }

    // ── Rejection notifications ───────────────────────────────────────────────

    public function test_submitter_is_notified_when_request_is_rejected(): void
    {
        [$paymentRequest, $instanceStage] = $this->makeAdvanceRequestInWorkflow();

        Notification::fake();

        app(WorkflowEngineService::class)->reject($instanceStage, $this->user, 'Insufficient documentation.');

        Notification::assertSentTo($this->user, RequestRejectedNotification::class);
    }

    // ── Send-back notifications ───────────────────────────────────────────────

    public function test_submitter_is_notified_when_request_is_sent_back(): void
    {
        [$paymentRequest, $instanceStage] = $this->makeAdvanceRequestInWorkflow();

        Notification::fake();

        app(WorkflowEngineService::class)->sendBack($instanceStage, $this->user, 'Please add receipts.');

        Notification::assertSentTo($this->user, RequestSentBackNotification::class);
    }

    // ── Disbursement notifications ────────────────────────────────────────────

    public function test_submitter_is_notified_when_advance_is_disbursed(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

        activity()
            ->performedOn($paymentRequest)
            ->causedBy($this->user)
            ->event('request.submitted')
            ->log('Submitted');

        Notification::fake();

        app(PaymentRequestService::class)->disburse($paymentRequest, 'bank_transfer', 'REF-001', $this->user);

        Notification::assertSentTo($this->user, RequestDisbursedNotification::class);
    }

    public function test_submitter_also_receives_retirement_reminder_when_advance_is_disbursed(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

        activity()
            ->performedOn($paymentRequest)
            ->causedBy($this->user)
            ->event('request.submitted')
            ->log('Submitted');

        Notification::fake();

        app(PaymentRequestService::class)->disburse($paymentRequest, 'cash', null, $this->user);

        Notification::assertSentTo($this->user, RetirementRequiredNotification::class);
    }

    public function test_expense_disbursement_does_not_send_retirement_reminder(): void
    {
        $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'approved']);

        activity()
            ->performedOn($paymentRequest)
            ->causedBy($this->user)
            ->event('request.submitted')
            ->log('Submitted');

        Notification::fake();

        app(PaymentRequestService::class)->disburse($paymentRequest, 'bank_transfer', null, $this->user);

        Notification::assertSentTo($this->user, RequestDisbursedNotification::class);
        Notification::assertNotSentTo($this->user, RetirementRequiredNotification::class);
    }
}
