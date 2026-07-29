<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PaymentMethod;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;

function approvedAdvanceForCashbookAutoEntry(float $amount, Branch|null $branch = null): PaymentRequest
{
    return PaymentRequest::factory()->advance()->create([
        'status' => 'approved',
        'total_amount' => $amount,
        'branch_id' => ($branch ?? test()->branch)->id,
    ]);
}
function approvedRetirementForCashbookAutoEntry(string $differenceType, float $differenceAmount = 50.00): RetirementRequest
{
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => test()->branch->id,
    ]);

    return RetirementRequest::factory()->approved()->create([
        'payment_request_id' => $advance->id,
        'difference_type' => $differenceType,
        'difference_amount' => $differenceAmount,
    ]);
}
test('disbursement creates credit cashbook entry', function () {
    $request = approvedAdvanceForCashbookAutoEntry(500.00);

    $this->actingAs($this->user)
        ->post(route('disbursements.store', $request), ['disbursement_method' => PaymentMethod::Cash->value]);

    $cashbook = Cashbook::where('branch_id', $request->branch_id)->first();
    expect($cashbook)->not->toBeNull();

    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'type' => 'credit',
        'sourceable_type' => PaymentRequest::class,
        'sourceable_id' => $request->id,
    ]);
});
test('disbursement decrements cashbook balance', function () {
    $request = approvedAdvanceForCashbookAutoEntry(300.00);

    $this->actingAs($this->user)
        ->post(route('disbursements.store', $request), ['disbursement_method' => PaymentMethod::Cash->value]);

    $cashbook = Cashbook::where('branch_id', $request->branch_id)->first();
    expect((float) $cashbook->balance)->toEqualWithDelta(-300.00, 0.01);
});
test('disbursement auto creates cashbook for branch', function () {
    $request = approvedAdvanceForCashbookAutoEntry(200.00);
    expect(Cashbook::where('branch_id', $request->branch_id)->first())->toBeNull();

    $this->actingAs($this->user)
        ->post(route('disbursements.store', $request), ['disbursement_method' => PaymentMethod::Cash->value]);

    expect(Cashbook::where('branch_id', $request->branch_id)->first())->not->toBeNull();
});
test('second disbursement on same branch accumulates balance', function () {
    $currency = Currency::factory()->create();

    Cashbook::create([
        'branch_id' => $this->branch->id,
        'currency_id' => $currency->id,
        'balance' => 500.00,
    ]);

    $first = approvedAdvanceForCashbookAutoEntry(200.00);
    $first->update(['currency_id' => $currency->id]);

    $second = approvedAdvanceForCashbookAutoEntry(300.00);
    $second->update(['currency_id' => $currency->id]);

    $this->actingAs($this->user)
        ->post(route('disbursements.store', $first), ['disbursement_method' => PaymentMethod::Cash->value]);

    $this->actingAs($this->user)
        ->post(route('disbursements.store', $second), ['disbursement_method' => PaymentMethod::Cash->value]);

    $cashbook = Cashbook::where('branch_id', $this->branch->id)->first();
    expect((float) $cashbook->balance)->toEqualWithDelta(0.00, 0.01);
    expect($cashbook->entries()->count())->toBe(2);
});
test('pay to staff retirement creates credit entry', function () {
    $retirement = approvedRetirementForCashbookAutoEntry('pay_to_staff', 75.00);

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement));

    $branchId = $retirement->paymentRequest()->value('branch_id');
    $cashbook = Cashbook::where('branch_id', $branchId)->first();
    expect($cashbook)->not->toBeNull();

    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'type' => 'credit',
        'sourceable_type' => RetirementRequest::class,
        'sourceable_id' => $retirement->id,
    ]);
});
test('pay to staff retirement decrements cashbook balance', function () {
    $retirement = approvedRetirementForCashbookAutoEntry('pay_to_staff', 75.00);

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement));

    $branchId = $retirement->paymentRequest()->value('branch_id');
    $cashbook = Cashbook::where('branch_id', $branchId)->first();
    expect((float) $cashbook->balance)->toEqualWithDelta(-75.00, 0.01);
});
test('refund to company retirement creates debit entry', function () {
    $retirement = approvedRetirementForCashbookAutoEntry('refund_to_company', 100.00);

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement));

    $branchId = $retirement->paymentRequest()->value('branch_id');
    $cashbook = Cashbook::where('branch_id', $branchId)->first();
    expect($cashbook)->not->toBeNull();

    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'sourceable_type' => RetirementRequest::class,
        'sourceable_id' => $retirement->id,
    ]);
});
test('refund to company retirement increments cashbook balance', function () {
    $retirement = approvedRetirementForCashbookAutoEntry('refund_to_company', 100.00);

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement));

    $branchId = $retirement->paymentRequest()->value('branch_id');
    $cashbook = Cashbook::where('branch_id', $branchId)->first();
    expect((float) $cashbook->balance)->toEqualWithDelta(100.00, 0.01);
});
test('nil difference retirement creates no cashbook entry', function () {
    $retirement = approvedRetirementForCashbookAutoEntry('nil', 0.00);

    $this->actingAs($this->user)
        ->post(route('retirement-requests.settle', $retirement));

    $this->assertDatabaseMissing('cashbook_entries', [
        'sourceable_type' => RetirementRequest::class,
        'sourceable_id' => $retirement->id,
    ]);
});
