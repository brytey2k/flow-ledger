<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;

function initForPaymentRequestController(): void
{
    test()->staff = Staff::factory()->create(['user_id' => test()->user->id, 'branch_id' => test()->branch->id]);
    test()->currency = Currency::factory()->create();
}
beforeEach(function () {
    initForPaymentRequestController();
});
test('index returns paginated list', function () {
    PaymentRequest::factory()->count(3)->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
    ]);

    $this->getJson('/api/payment-requests')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
});
test('index requires permission', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessPaymentRequests->value);
    $this->user->unsetRelation('roles')->unsetRelation('permissions');

    $this->getJson('/api/payment-requests')->assertForbidden();
});
test('store creates draft payment request', function () {
    $response = $this->postJson('/api/payment-requests', [
        'currency_id' => $this->currency->id,
        'type' => 'advance',
        'notes' => 'Test advance',
        'items' => [
            ['description' => 'Item 1', 'amount' => 100.00, 'cost_code_id' => null],
        ],
    ])->assertCreated();

    $response->assertJsonPath('data.status', 'draft');
    $this->assertDatabaseHas('payment_requests', ['notes' => 'Test advance', 'status' => 'draft']);
});
test('store requires at least one item', function () {
    $this->postJson('/api/payment-requests', [
        'currency_id' => $this->currency->id,
        'type' => 'advance',
        'items' => [],
    ])->assertUnprocessable()->assertJsonValidationErrors('items');
});
test('store requires create permission', function () {
    $this->role->revokePermissionTo(PermissionKey::CreatePaymentRequest->value);
    $this->user->unsetRelation('roles')->unsetRelation('permissions');

    $this->postJson('/api/payment-requests', [
        'currency_id' => $this->currency->id,
        'type' => 'advance',
        'items' => [['description' => 'X', 'amount' => 10]],
    ])->assertForbidden();
});
test('show returns request detail', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
    ]);

    $this->getJson("/api/payment-requests/{$pr->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $pr->id);
});
test('show 403 for out of scope branch', function () {
    $otherBranch = App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $otherBranch->id,
        'currency_id' => $this->currency->id,
    ]);

    $this->getJson("/api/payment-requests/{$pr->id}")->assertForbidden();
});
test('update modifies draft request', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'type' => 'advance',
    ]);

    $this->putJson("/api/payment-requests/{$pr->id}", [
        'currency_id' => $this->currency->id,
        'notes' => 'Updated notes',
        'items' => [['description' => 'Updated item', 'amount' => 200.00]],
    ])->assertOk()->assertJsonPath('data.notes', 'Updated notes');
});
test('update rejects non draft non sent back', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'approved',
    ]);

    $this->putJson("/api/payment-requests/{$pr->id}", [
        'currency_id' => $this->currency->id,
        'items' => [['description' => 'X', 'amount' => 10]],
    ])->assertStatus(422);
});
test('destroy deletes draft request', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
    ]);

    $this->deleteJson("/api/payment-requests/{$pr->id}")->assertNoContent();

    $this->assertSoftDeleted('payment_requests', ['id' => $pr->id]);
});
test('destroy rejects non draft', function () {
    $pr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'in_workflow',
    ]);

    $this->deleteJson("/api/payment-requests/{$pr->id}")->assertStatus(422);
});
