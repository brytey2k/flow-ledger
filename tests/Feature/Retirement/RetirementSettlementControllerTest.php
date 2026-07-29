<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;

function approvedRetirementForRetirementSettlementController(): RetirementRequest
{
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => test()->branch->id,
    ]);

    return RetirementRequest::factory()->create([
        'payment_request_id' => $advance->id,
        'status' => 'approved',
        'difference_type' => 'refund_to_company',
        'difference_amount' => 50.00,
    ]);
}
test('guest is redirected', function () {
    $retirement = approvedRetirementForRetirementSettlementController();

    $this->post(route('retirement-requests.settle', $retirement))
        ->assertRedirect(route('login'));
});
test('user without permission cannot settle', function () {
    $this->role->revokePermissionTo(PermissionKey::SettleRetirements->value);
    $retirement = approvedRetirementForRetirementSettlementController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement))
        ->assertForbidden();
});
test('approved retirement can be settled', function () {
    $retirement = approvedRetirementForRetirementSettlementController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement), [
            'settlement_notes' => 'Cheque #1234 issued.',
        ])
        ->assertRedirect(route('retirement-requests.show', $retirement));

    $retirement->refresh();
    expect($retirement->status)->toBe('settled');
    expect($retirement->settled_at)->not->toBeNull();
    expect($retirement->settlement_notes)->toBe('Cheque #1234 issued.');
    expect($retirement->settled_by_user_id)->toBe($this->user->id);
});
test('settling retirement marks payment request as retired', function () {
    $retirement = approvedRetirementForRetirementSettlementController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement))
        ->assertRedirect();

    expect($retirement->paymentRequest->fresh()->status)->toBe('retired');
});
test('settlement notes are optional', function () {
    $retirement = approvedRetirementForRetirementSettlementController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement))
        ->assertRedirect(route('retirement-requests.show', $retirement));

    expect($retirement->fresh()->status)->toBe('settled');
});
test('nil difference retirement can also be settled', function () {
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $advance->id,
        'status' => 'approved',
        'difference_type' => 'nil',
        'difference_amount' => 0,
    ]);

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement))
        ->assertRedirect(route('retirement-requests.show', $retirement));

    expect($retirement->fresh()->status)->toBe('settled');
});
test('non approved retirement cannot be settled', function () {
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $advance->id,
        'status' => 'in_workflow',
    ]);

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement))
        ->assertRedirect();

    expect($retirement->fresh()->status)->toBe('in_workflow');
});
test('settlement is logged in activity', function () {
    $retirement = approvedRetirementForRetirementSettlementController();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement));

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => RetirementRequest::class,
        'subject_id' => $retirement->id,
        'event' => 'retirement.settled',
    ]);
});
test('user cannot settle retirement from different branch', function () {
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $advance->id,
        'status' => 'approved',
    ]);

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement))
        ->assertForbidden();

    expect($retirement->fresh()->status)->toBe('approved');
});
test('settlement fails when insufficient cashbook balance for pay to staff', function () {
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'total_amount' => 100.00,
        'branch_id' => $this->branch->id,
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $advance->id,
        'status' => 'approved',
        'difference_type' => 'pay_to_staff',
        'difference_amount' => 50.00,
    ]);

    // Pre-populate cashbook with insufficient balance for paying staff
    Cashbook::create([
        'branch_id' => $this->branch->id,
        'currency_id' => $advance->currency_id,
        'balance' => 30.00,
    ]);

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement))
        ->assertRedirect()
        ->assertSessionHas('error');

    expect($retirement->fresh()->status)->toBe('approved');
});
