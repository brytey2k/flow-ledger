<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceActorClaim;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;

function initForDisbursementController(): void
{
    test()->staff = Staff::factory()->create(['user_id' => test()->user->id, 'branch_id' => test()->branch->id]);
    test()->currency = Currency::factory()->create();
}
beforeEach(function () {
    initForDisbursementController();
});
test('index returns approved requests', function () {
    PaymentRequest::factory()->count(2)->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);
    PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'draft',
    ]);

    $this->getJson('/api/disbursements')
        ->assertOk()
        ->assertJsonPath('meta.total', 2);
});
test('index excludes requests submitted by the disbursement user', function () {
    $participated = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);
    $eligible = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);
    $template = WorkflowTemplate::factory()->advance()->create();
    WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $participated->id,
        'submitter_user_id' => $this->user->id,
        'status' => 'completed',
    ]);

    $this->getJson('/api/disbursements')
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonPath('data.0.id', $eligible->id);
});
test('index requires disburse permission', function () {
    $this->role->revokePermissionTo(PermissionKey::DisburseRequests->value);
    $this->user->unsetRelation('roles')->unsetRelation('permissions');

    $this->getJson('/api/disbursements')->assertForbidden();
});
test('store disburses approved request', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);

    $this->postJson("/api/disbursements/{$pr->id}", [
        'disbursement_reference' => 'REF-001',
    ])->assertOk()
        ->assertJsonPath('data.status', 'disbursed');

    expect($pr->fresh()->disbursement_method?->value)->toBe('cash');
});
test('store rejects an approver attempting to disburse the same request', function () {
    $paymentRequest = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $instance = WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'completed',
    ]);
    $instanceStage = WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'approved',
        'completed_at' => now(),
    ]);
    WorkflowInstanceActorClaim::create([
        'workflow_instance_id' => $instance->id,
        'workflow_instance_stage_id' => $instanceStage->id,
        'user_id' => $this->user->id,
    ]);

    $this->postJson("/api/disbursements/{$paymentRequest->id}")->assertForbidden();

    expect($paymentRequest->fresh()->status)->toBe('approved');
});
test('store rejects non approved request', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'draft',
    ]);

    $this->postJson("/api/disbursements/{$pr->id}")->assertStatus(422);
});
test('store rejects out of scope branch', function () {
    $otherBranch = App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $otherBranch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);

    $this->postJson("/api/disbursements/{$pr->id}")->assertForbidden();
});
test('store rejects disbursement when insufficient cashbook balance', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
        'total_amount' => 100.00,
    ]);

    Cashbook::create([
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'balance' => 50.00,
    ]);

    $this->postJson("/api/disbursements/{$pr->id}")->assertStatus(422)
        ->assertJsonPath('message', 'Insufficient cashbook balance for disbursement.');

    $this->assertDatabaseHas('payment_requests', ['id' => $pr->id, 'status' => 'approved']);
});
test('store rejects a caller supplied disbursement method', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);

    $this->postJson("/api/disbursements/{$pr->id}", [
        'disbursement_method' => 'bank_transfer',
    ])->assertUnprocessable();

    expect($pr->fresh()->status)->toBe('approved');
});
