<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PaymentMethod;
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\PaymentRequest;

test('guest is redirected from index', function () {
    $response = $this->get(route('disbursements.index'));

    $response->assertRedirect(route('login'));
});
test('guest cannot disburse', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

    $response = $this->post(route('disbursements.store', $paymentRequest), [
        'disbursement_method' => PaymentMethod::Cash->value,
    ]);

    $response->assertRedirect(route('login'));
});
test('user without permission cannot access index', function () {
    $this->role->revokePermissionTo(PermissionKey::DisburseRequests->value);

    $response = $this->actingAs($this->user)->get(route('disbursements.index'));

    $response->assertForbidden();
});
test('user without permission cannot disburse', function () {
    $this->role->revokePermissionTo(PermissionKey::DisburseRequests->value);
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
        'disbursement_method' => PaymentMethod::Cash->value,
    ]);

    $response->assertForbidden();
});
test('authorised user sees disbursements index', function () {
    PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);
    PaymentRequest::factory()->advance()->create(['status' => 'draft', 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->get(route('disbursements.index'));

    $response->assertOk();
    $response->assertViewIs('tenant.disbursements.index');
});
test('index only shows approved requests', function () {
    $approved = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);
    PaymentRequest::factory()->advance()->create(['status' => 'draft', 'branch_id' => $this->branch->id]);
    PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->get(route('disbursements.index'));

    $response->assertOk();
    $response->assertViewHas('requests', fn($requests) => $requests->contains($approved));
    $response->assertViewHas('requests', fn($requests) => $requests->total() === 1);
});
test('authorised user can disburse approved request', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
        'disbursement_method' => PaymentMethod::BankTransfer->value,
        'disbursement_reference' => 'TXN-001',
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('payment_requests', [
        'id' => $paymentRequest->id,
        'status' => 'disbursed',
        'disbursement_method' => PaymentMethod::BankTransfer->value,
        'disbursement_reference' => 'TXN-001',
        'disbursed_by_user_id' => $this->user->id,
    ]);
});
test('disburse without reference is allowed', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
        'disbursement_method' => PaymentMethod::Cash->value,
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $this->assertDatabaseHas('payment_requests', [
        'id' => $paymentRequest->id,
        'status' => 'disbursed',
        'disbursement_reference' => null,
    ]);
});
test('cannot disburse non approved request', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft', 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
        'disbursement_method' => PaymentMethod::Cash->value,
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('error');

    $this->assertDatabaseHas('payment_requests', ['id' => $paymentRequest->id, 'status' => 'draft']);
});
test('disbursement method is required', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
        'disbursement_method' => '',
    ]);

    $response->assertSessionHasErrors('disbursement_method');
    $this->assertDatabaseHas('payment_requests', ['id' => $paymentRequest->id, 'status' => 'approved']);
});
test('disburse logs activity', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

    $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
        'disbursement_method' => PaymentMethod::MobileMoney->value,
        'disbursement_reference' => 'MM-999',
    ]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => PaymentRequest::class,
        'subject_id' => $paymentRequest->id,
        'event' => 'request.disbursed',
    ]);
});
test('cannot disburse when insufficient cashbook balance', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'approved',
        'branch_id' => $this->branch->id,
        'total_amount' => 100.00,
    ]);

    // Pre-populate cashbook with insufficient balance
    Cashbook::create([
        'branch_id' => $this->branch->id,
        'currency_id' => $paymentRequest->currency_id,
        'balance' => 50.00,
    ]);

    $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
        'disbursement_method' => PaymentMethod::Cash->value,
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('error');

    $this->assertDatabaseHas('payment_requests', ['id' => $paymentRequest->id, 'status' => 'approved']);
});
test('authorised user can disburse approved expense', function () {
    $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
        'disbursement_method' => PaymentMethod::BankTransfer->value,
        'disbursement_reference' => 'TXN-EXP-001',
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('payment_requests', [
        'id' => $paymentRequest->id,
        'status' => 'disbursed',
        'disbursement_method' => PaymentMethod::BankTransfer->value,
    ]);
});
