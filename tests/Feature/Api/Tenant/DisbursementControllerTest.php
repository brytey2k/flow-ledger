<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;

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
        'disbursement_method' => 'cash',
        'disbursement_reference' => 'REF-001',
    ])->assertOk()
        ->assertJsonPath('data.status', 'disbursed');
});
test('store rejects non approved request', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'draft',
    ]);

    $this->postJson("/api/disbursements/{$pr->id}", [
        'disbursement_method' => 'cash',
    ])->assertStatus(422);
});
test('store rejects out of scope branch', function () {
    $otherBranch = App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $otherBranch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);

    $this->postJson("/api/disbursements/{$pr->id}", [
        'disbursement_method' => 'cash',
    ])->assertForbidden();
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

    $this->postJson("/api/disbursements/{$pr->id}", [
        'disbursement_method' => 'cash',
    ])->assertStatus(422)
        ->assertJsonPath('message', 'Insufficient cashbook balance for disbursement.');

    $this->assertDatabaseHas('payment_requests', ['id' => $pr->id, 'status' => 'approved']);
});
test('store requires valid disbursement method', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);

    $this->postJson("/api/disbursements/{$pr->id}", [
        'disbursement_method' => 'wire_transfer',
    ])->assertUnprocessable();
});
