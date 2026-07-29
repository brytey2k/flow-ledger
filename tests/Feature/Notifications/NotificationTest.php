<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PaymentMethod;
use App\Models\Role;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\User;
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
use Illuminate\Support\Str;

function makeAdvanceRequestInWorkflow(): array
{
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);
    $approverRole = Role::create(['name' => 'approver_' . Str::uuid(), 'guard_name' => 'web']);
    $stage->roles()->attach($approverRole->id);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    app(PaymentRequestService::class)->submit($paymentRequest, test()->user);

    $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();

    return [$paymentRequest, $instanceStage];
}
test('stage approvers are notified when workflow starts', function () {
    Notification::fake();

    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);
    $stage->roles()->attach($this->role->id);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    $submitter = User::factory()->create();

    app(PaymentRequestService::class)->submit($paymentRequest, $submitter);

    Notification::assertSentTo($this->user, StageReadyForApprovalNotification::class);
});
test('next stage approvers are notified on advance', function () {
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
    $submitter = User::factory()->create();
    app(PaymentRequestService::class)->submit($paymentRequest, $submitter);

    $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();

    Notification::fake();

    app(WorkflowEngineService::class)->approve($instanceStage, $this->user);

    Notification::assertSentTo($this->user, StageReadyForApprovalNotification::class);
});
test('submitter is notified when request is fully approved', function () {
    [$paymentRequest, $instanceStage] = makeAdvanceRequestInWorkflow();

    Notification::fake();

    app(WorkflowEngineService::class)->approve($instanceStage, $this->user);

    Notification::assertSentTo($this->user, RequestApprovedNotification::class);
});
test('submitter is notified when retirement is fully approved', function () {
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
    $approverRole = Role::create(['name' => 'approver_' . Str::uuid(), 'guard_name' => 'web']);
    $stage->roles()->attach($approverRole->id);

    app(RetirementService::class)->submit($retirement, $this->user);

    $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();

    Notification::fake();

    app(WorkflowEngineService::class)->approve($instanceStage, $this->user);

    Notification::assertSentTo($this->user, RetirementApprovedNotification::class);
});
test('submitter is notified when request is rejected', function () {
    [$paymentRequest, $instanceStage] = makeAdvanceRequestInWorkflow();

    Notification::fake();

    app(WorkflowEngineService::class)->reject($instanceStage, $this->user, 'Insufficient documentation.');

    Notification::assertSentTo($this->user, RequestRejectedNotification::class);
});
test('submitter is notified when request is sent back', function () {
    [$paymentRequest, $instanceStage] = makeAdvanceRequestInWorkflow();

    Notification::fake();

    app(WorkflowEngineService::class)->sendBack($instanceStage, $this->user, 'Please add receipts.');

    Notification::assertSentTo($this->user, RequestSentBackNotification::class);
});
test('submitter is notified when advance is disbursed', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

    activity()
        ->performedOn($paymentRequest)
        ->causedBy($this->user)
        ->event('request.submitted')
        ->log('Submitted');

    Notification::fake();

    app(PaymentRequestService::class)->disburse($paymentRequest, new App\DTOs\Tenant\DisbursePaymentRequestDto(method: PaymentMethod::BankTransfer, reference: 'REF-001'), $this->user);

    Notification::assertSentTo($this->user, RequestDisbursedNotification::class);
});
test('submitter also receives retirement reminder when advance is disbursed', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

    activity()
        ->performedOn($paymentRequest)
        ->causedBy($this->user)
        ->event('request.submitted')
        ->log('Submitted');

    Notification::fake();

    app(PaymentRequestService::class)->disburse($paymentRequest, new App\DTOs\Tenant\DisbursePaymentRequestDto(method: PaymentMethod::Cash, reference: null), $this->user);

    Notification::assertSentTo($this->user, RetirementRequiredNotification::class);
});
test('expense disbursement does not send retirement reminder', function () {
    $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'approved']);

    activity()
        ->performedOn($paymentRequest)
        ->causedBy($this->user)
        ->event('request.submitted')
        ->log('Submitted');

    Notification::fake();

    app(PaymentRequestService::class)->disburse($paymentRequest, new App\DTOs\Tenant\DisbursePaymentRequestDto(method: PaymentMethod::BankTransfer, reference: null), $this->user);

    Notification::assertSentTo($this->user, RequestDisbursedNotification::class);
    Notification::assertNotSentTo($this->user, RetirementRequiredNotification::class);
});
