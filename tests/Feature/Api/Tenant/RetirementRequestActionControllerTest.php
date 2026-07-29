<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;

function initForRetirementRequestActionController(): void
{
    test()->staff = Staff::factory()->create(['user_id' => test()->user->id, 'branch_id' => test()->branch->id]);
    test()->currency = Currency::factory()->create();
    test()->disbursedPr = PaymentRequest::factory()->advance()->create([
        'staff_id' => test()->staff->id,
        'branch_id' => test()->branch->id,
        'currency_id' => test()->currency->id,
        'status' => 'disbursed',
    ]);
}
beforeEach(function () {
    initForRetirementRequestActionController();
});
function makeDraftRetirement(PaymentRequest|null $pr = null): RetirementRequest
{
    return RetirementRequest::factory()->create([
        'payment_request_id' => ($pr ?? test()->disbursedPr)->id,
        'status' => 'draft',
    ]);
}
function createRetirementTemplate(): void
{
    $template = WorkflowTemplate::factory()->create(['type' => 'retirement', 'branch_id' => null]);
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);

    // Use a separate role so the submitting user is not auto-approved as the approver
    $approverRole = App\Models\Role::create(['name' => 'ret_approver_' . uniqid(), 'guard_name' => 'web']);
    $stage->roles()->attach($approverRole->id);
}
test('submit transitions draft to in workflow', function () {
    createRetirementTemplate();
    $retirement = makeDraftRetirement();

    $this->postJson("/api/retirement-requests/{$retirement->id}/submit")
        ->assertOk()
        ->assertJsonPath('data.status', 'in_workflow');
});
test('submit fails if not draft', function () {
    $retirement = RetirementRequest::factory()->inWorkflow()->create([
        'payment_request_id' => $this->disbursedPr->id,
    ]);

    $this->postJson("/api/retirement-requests/{$retirement->id}/submit")
        ->assertStatus(422);
});
test('submit 403 for non owner', function () {
    $otherStaff = Staff::factory()->create(['branch_id' => $this->branch->id]);
    $otherPr = PaymentRequest::factory()->advance()->create([
        'staff_id' => $otherStaff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'disbursed',
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $otherPr->id,
        'status' => 'draft',
    ]);

    $this->postJson("/api/retirement-requests/{$retirement->id}/submit")
        ->assertForbidden();
});
test('cancel transitions draft to cancelled', function () {
    $retirement = makeDraftRetirement();

    $this->postJson("/api/retirement-requests/{$retirement->id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});
test('cancel fails for non cancellable status', function () {
    $retirement = RetirementRequest::factory()->approved()->create([
        'payment_request_id' => $this->disbursedPr->id,
    ]);

    $this->postJson("/api/retirement-requests/{$retirement->id}/cancel")
        ->assertStatus(422);
});
test('resubmit fails if not sent back', function () {
    $retirement = makeDraftRetirement();

    $this->postJson("/api/retirement-requests/{$retirement->id}/resubmit")
        ->assertStatus(422);
});
