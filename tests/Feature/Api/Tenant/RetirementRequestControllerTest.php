<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;

function initForRetirementRequestController(): void
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
    initForRetirementRequestController();
});
function makeDisbursedPr(): PaymentRequest
{
    return PaymentRequest::factory()->advance()->create([
        'staff_id' => test()->staff->id,
        'branch_id' => test()->branch->id,
        'currency_id' => test()->currency->id,
        'status' => 'disbursed',
    ]);
}
test('index returns paginated list', function () {
    RetirementRequest::factory()->create(['payment_request_id' => $this->disbursedPr->id]);
    RetirementRequest::factory()->create(['payment_request_id' => makeDisbursedPr()->id]);

    $this->getJson('/api/retirement-requests')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});
test('index requires permission', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessRetirementRequests->value);
    $this->user->unsetRelation('roles')->unsetRelation('permissions');

    $this->getJson('/api/retirement-requests')->assertForbidden();
});
test('store creates draft retirement request', function () {
    $costCode = CostCode::factory()->create();

    $this->postJson('/api/retirement-requests', [
        'payment_request_id' => $this->disbursedPr->id,
        'notes' => 'Retirement test',
        'difference_type' => 'nil',
        'did_not_spend_money' => false,
        'items' => [
            ['description' => 'Receipt 1', 'amount' => 100.00, 'cost_code_id' => $costCode->id],
        ],
    ])->assertCreated()
        ->assertJsonPath('data.status', 'draft');
});
test('store rejects non disbursed payment request', function () {
    $pr = PaymentRequest::factory()->advance()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);
    $costCode = CostCode::factory()->create();

    $this->postJson('/api/retirement-requests', [
        'payment_request_id' => $pr->id,
        'difference_type' => 'nil',
        'did_not_spend_money' => false,
        'items' => [
            ['description' => 'X', 'amount' => 100, 'cost_code_id' => $costCode->id],
        ],
    ])->assertStatus(422);
});
test('store rejects expense payment request', function () {
    $expensePr = PaymentRequest::factory()->expense()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'disbursed',
    ]);
    $costCode = CostCode::factory()->create();

    $this->postJson('/api/retirement-requests', [
        'payment_request_id' => $expensePr->id,
        'difference_type' => 'nil',
        'did_not_spend_money' => false,
        'items' => [
            ['description' => 'X', 'amount' => 100, 'cost_code_id' => $costCode->id],
        ],
    ])->assertStatus(422);
});
test('show returns detail', function () {
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $this->disbursedPr->id,
    ]);

    $this->getJson("/api/retirement-requests/{$retirement->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $retirement->id);
});
test('show 403 for out of scope branch', function () {
    $otherBranch = App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
    $otherPr = PaymentRequest::factory()->advance()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $otherBranch->id,
        'currency_id' => $this->currency->id,
        'status' => 'disbursed',
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $otherPr->id,
    ]);

    $this->getJson("/api/retirement-requests/{$retirement->id}")->assertForbidden();
});
test('update modifies draft retirement', function () {
    $costCode = CostCode::factory()->create();
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $this->disbursedPr->id,
        'status' => 'draft',
    ]);

    $this->putJson("/api/retirement-requests/{$retirement->id}", [
        'notes' => 'Updated retirement',
        'difference_type' => 'nil',
        'did_not_spend_money' => false,
        'items' => [
            ['description' => 'Updated item', 'amount' => 150.00, 'cost_code_id' => $costCode->id],
        ],
    ])->assertOk();
});
test('update rejects non draft non sent back', function () {
    $costCode = CostCode::factory()->create();
    $retirement = RetirementRequest::factory()->inWorkflow()->create([
        'payment_request_id' => $this->disbursedPr->id,
    ]);

    $this->putJson("/api/retirement-requests/{$retirement->id}", [
        'difference_type' => 'nil',
        'did_not_spend_money' => false,
        'items' => [
            ['description' => 'X', 'amount' => 50, 'cost_code_id' => $costCode->id],
        ],
    ])->assertStatus(422);
});
