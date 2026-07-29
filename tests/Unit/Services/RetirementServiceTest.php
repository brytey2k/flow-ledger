<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\DTOs\Tenant\CreateRetirementRequestDto;
use App\DTOs\Tenant\RetirementRequestItemDto;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\RetirementService;

function makeServiceForRetirementService(): RetirementService
{
    return app(RetirementService::class);
}
function makeDto(float $amount = 500.0, string|null $notes = null, bool $didNotSpendMoney = false): CreateRetirementRequestDto
{
    $costCode = CostCode::factory()->create();

    return new CreateRetirementRequestDto(
        notes: $notes,
        didNotSpendMoney: $didNotSpendMoney,
        items: [
            new RetirementRequestItemDto(
                description: 'Test item',
                amount: $amount,
                costCodeId: $costCode->id,
                receiptNumber: null,
            ),
        ],
    );
}
function disbursedAdvanceForRetirementService(float $totalAmount = 500.0): PaymentRequest
{
    return PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'total_amount' => $totalAmount,
    ]);
}
test('cancel without active instance sets status to cancelled', function () {
    $paymentRequest = disbursedAdvanceForRetirementService();
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'draft',
    ]);

    makeServiceForRetirementService()->cancel($retirement, $this->user);

    $this->assertDatabaseHas('retirement_requests', [
        'id' => $retirement->id,
        'status' => 'cancelled',
    ]);
});
test('cancel with active instance cancels instance and stages', function () {
    $template = WorkflowTemplate::factory()->retirement()->create();
    $stageDef = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);

    $paymentRequest = disbursedAdvanceForRetirementService();
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'in_workflow',
    ]);

    $instance = WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => RetirementRequest::class,
        'workflowable_id' => $retirement->id,
        'status' => 'in_progress',
    ]);

    WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDef->id,
        'status' => 'active',
        'started_at' => now(),
    ]);

    makeServiceForRetirementService()->cancel($retirement, $this->user);

    $this->assertDatabaseHas('retirement_requests', ['id' => $retirement->id, 'status' => 'cancelled']);
    $this->assertDatabaseHas('workflow_instances', ['id' => $instance->id, 'status' => 'cancelled']);
    $this->assertDatabaseHas('workflow_instance_stages', [
        'workflow_instance_id' => $instance->id,
        'status' => 'cancelled',
    ]);
});
test('create draft sets nil difference type when amounts match', function () {
    $paymentRequest = disbursedAdvanceForRetirementService(500.0);

    $retirement = makeServiceForRetirementService()->createDraft($paymentRequest, makeDto(500.0), $this->user);

    $this->assertDatabaseHas('retirement_requests', [
        'id' => $retirement->id,
        'difference_type' => 'nil',
        'difference_amount' => '0.00',
    ]);
});
test('create draft sets pay to staff when expended exceeds advance', function () {
    $paymentRequest = disbursedAdvanceForRetirementService(500.0);

    $retirement = makeServiceForRetirementService()->createDraft($paymentRequest, makeDto(600.0), $this->user);

    $this->assertDatabaseHas('retirement_requests', [
        'id' => $retirement->id,
        'difference_type' => 'pay_to_staff',
        'difference_amount' => '100.00',
    ]);
});
test('create draft sets refund to company when expended is less than advance', function () {
    $paymentRequest = disbursedAdvanceForRetirementService(500.0);

    $retirement = makeServiceForRetirementService()->createDraft($paymentRequest, makeDto(300.0), $this->user);

    $this->assertDatabaseHas('retirement_requests', [
        'id' => $retirement->id,
        'difference_type' => 'refund_to_company',
        'difference_amount' => '200.00',
    ]);
});
test('create draft throws validation exception when retirement already exists', function () {
    $paymentRequest = disbursedAdvanceForRetirementService(500.0);

    // Create the first retirement
    makeServiceForRetirementService()->createDraft($paymentRequest, makeDto(500.0), $this->user);

    $this->expectException(Illuminate\Validation\ValidationException::class);

    // Second attempt should fail
    makeServiceForRetirementService()->createDraft($paymentRequest, makeDto(500.0), $this->user);
});
test('create draft duplicate check ignores cancelled retirements', function () {
    $paymentRequest = disbursedAdvanceForRetirementService(500.0);

    // Create and cancel a retirement
    $existing = RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'cancelled',
    ]);

    // A new retirement for the same advance should be allowed
    $retirement = makeServiceForRetirementService()->createDraft($paymentRequest, makeDto(500.0), $this->user);

    $this->assertDatabaseHas('retirement_requests', [
        'id' => $retirement->id,
        'status' => 'draft',
    ]);
});
test('create draft can record a no spend retirement', function () {
    $paymentRequest = disbursedAdvanceForRetirementService(500.0);

    $retirement = makeServiceForRetirementService()->createDraft(
        $paymentRequest,
        new CreateRetirementRequestDto(
            notes: null,
            didNotSpendMoney: true,
            items: [],
        ),
        $this->user,
    );

    $this->assertDatabaseHas('retirement_requests', [
        'id' => $retirement->id,
        'no_money_spent' => 1,
        'total_amount_expended' => '0.00',
        'difference_amount' => '500.00',
        'difference_type' => 'refund_to_company',
    ]);
});
test('update draft sets nil when amounts match', function () {
    $paymentRequest = disbursedAdvanceForRetirementService(800.0);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'draft',
        'difference_type' => 'refund_to_company',
    ]);

    $updated = makeServiceForRetirementService()->updateDraft($retirement, makeDto(800.0), $this->user);

    expect($updated->difference_type)->toBe('nil');
});
test('update sent back sets nil when amounts match', function () {
    $paymentRequest = disbursedAdvanceForRetirementService(300.0);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'sent_back',
        'difference_type' => 'pay_to_staff',
    ]);

    $updated = makeServiceForRetirementService()->updateSentBack($retirement, makeDto(300.0), $this->user);

    expect($updated->difference_type)->toBe('nil');
});
test('submit uses branch specific template when available', function () {
    $branch = Branch::factory()->create();
    $masterTemplate = WorkflowTemplate::factory()->retirement()->create(['branch_id' => null]);
    $branchTemplate = WorkflowTemplate::factory()->retirement()->create(['branch_id' => $branch->id]);

    WorkflowStage::factory()->create(['workflow_template_id' => $branchTemplate->id, 'display_order' => 1]);

    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'branch_id' => $branch->id,
        'total_amount' => 500.0,
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'draft',
    ]);

    makeServiceForRetirementService()->submit($retirement, $this->user);

    $instance = WorkflowInstance::where('workflowable_id', $retirement->id)
        ->where('workflowable_type', RetirementRequest::class)
        ->firstOrFail();

    expect($instance->workflow_template_id)->toEqual($branchTemplate->id);
    $this->assertNotEquals($masterTemplate->id, $instance->workflow_template_id);
});
test('submit falls back to master template when no branch template', function () {
    $branch = Branch::factory()->create();
    $masterTemplate = WorkflowTemplate::factory()->retirement()->create(['branch_id' => null]);

    WorkflowStage::factory()->create(['workflow_template_id' => $masterTemplate->id, 'display_order' => 1]);

    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'branch_id' => $branch->id,
        'total_amount' => 500.0,
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'status' => 'draft',
    ]);

    makeServiceForRetirementService()->submit($retirement, $this->user);

    $instance = WorkflowInstance::where('workflowable_id', $retirement->id)
        ->where('workflowable_type', RetirementRequest::class)
        ->firstOrFail();

    expect($instance->workflow_template_id)->toEqual($masterTemplate->id);
});
