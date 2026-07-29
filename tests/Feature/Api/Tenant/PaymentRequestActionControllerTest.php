<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;

function initForPaymentRequestActionController(): void
{
    test()->staff = Staff::factory()->create(['user_id' => test()->user->id, 'branch_id' => test()->branch->id]);
    test()->currency = Currency::factory()->create();
}
beforeEach(function () {
    initForPaymentRequestActionController();
});
test('submit transitions draft to in workflow', function () {
    $template = WorkflowTemplate::factory()->create(['type' => 'advance', 'branch_id' => null]);
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'type' => 'advance',
    ]);

    $this->postJson("/api/payment-requests/{$pr->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', 'in_workflow');
});
test('submit fails if not draft', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'in_workflow',
    ]);

    $this->postJson("/api/payment-requests/{$pr->id}/submit")->assertStatus(422);
});
test('submit fails if no workflow template', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'type' => 'advance',
    ]);

    // No WorkflowTemplate created — should fail
    $this->postJson("/api/payment-requests/{$pr->id}/submit")
        ->assertStatus(422)
        ->assertJsonFragment(['message' => 'No workflow template is configured for this request type and branch.']);
});
test('submit 403 for non owner', function () {
    $otherStaff = Staff::factory()->create(['branch_id' => $this->branch->id]);
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $otherStaff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
    ]);

    $this->postJson("/api/payment-requests/{$pr->id}/submit")->assertForbidden();
});
test('cancel transitions draft to cancelled', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
    ]);

    $this->postJson("/api/payment-requests/{$pr->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});
test('cancel fails for disbursed request', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'disbursed',
    ]);

    $this->postJson("/api/payment-requests/{$pr->id}/cancel")->assertStatus(422);
});
test('resubmit fails if not sent back', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
    ]);

    $this->postJson("/api/payment-requests/{$pr->id}/resubmit")->assertStatus(422);
});
