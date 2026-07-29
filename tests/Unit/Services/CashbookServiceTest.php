<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\DTOs\Tenant\CashbookEntryDto;
use App\Events\CashbookBalanceChanged;
use App\Exceptions\InsufficientCashbookBalanceException;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Services\CashbookService;
use Illuminate\Support\Facades\Event;

function makeServiceForCashbookService(): CashbookService
{
    return app(CashbookService::class);
}
function makeCashbookForCashbookService(float $balance = 0.0): Cashbook
{
    $currency = Currency::factory()->create();

    return Cashbook::create([
        'branch_id' => test()->branch->id,
        'currency_id' => $currency->id,
        'balance' => $balance,
    ]);
}
function approvedAdvanceForCashbookService(float $amount = 500.0): PaymentRequest
{
    return PaymentRequest::factory()->advance()->create([
        'status' => 'approved',
        'branch_id' => test()->branch->id,
        'total_amount' => $amount,
    ]);
}
function approvedRetirementForCashbookService(string $differenceType, float $differenceAmount, float $advanceAmount = 500.0): RetirementRequest
{
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'branch_id' => test()->branch->id,
        'total_amount' => $advanceAmount,
    ]);

    return RetirementRequest::factory()->approved()->create([
        'payment_request_id' => $advance->id,
        'difference_type' => $differenceType,
        'difference_amount' => $differenceAmount,
    ]);
}
test('record disbursement creates credit entry', function () {
    $request = approvedAdvanceForCashbookService(300.0);

    makeServiceForCashbookService()->recordDisbursement($request, $this->user);

    $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'type' => 'credit',
        'amount' => '300.00',
        'sourceable_type' => PaymentRequest::class,
        'sourceable_id' => $request->id,
    ]);
});
test('record disbursement decrements balance', function () {
    $cashbook = makeCashbookForCashbookService(1000.0);
    $request = approvedAdvanceForCashbookService(300.0);

    makeServiceForCashbookService()->recordDisbursement($request, $this->user);

    expect((float) $cashbook->fresh()->balance)->toEqualWithDelta(700.0, 0.01);
});
test('record disbursement auto creates cashbook when none exists', function () {
    expect(Cashbook::where('branch_id', $this->branch->id)->first())->toBeNull();
    $request = approvedAdvanceForCashbookService(200.0);

    makeServiceForCashbookService()->recordDisbursement($request, $this->user);

    expect(Cashbook::where('branch_id', $this->branch->id)->first())->not->toBeNull();
});
test('record disbursement allows disbursement when balance is zero', function () {
    makeCashbookForCashbookService(0.0);
    $request = approvedAdvanceForCashbookService(500.0);

    // Zero balance must not block — allows going negative.
    makeServiceForCashbookService()->recordDisbursement($request, $this->user);

    $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
    expect((float) $cashbook->balance)->toEqualWithDelta(-500.0, 0.01);
});
test('record disbursement throws when balance is negative', function () {
    makeCashbookForCashbookService(-200.0);
    $request = approvedAdvanceForCashbookService(500.0);

    $this->expectException(InsufficientCashbookBalanceException::class);
    makeServiceForCashbookService()->recordDisbursement($request, $this->user);
});
test('record disbursement throws when balance is positive but insufficient', function () {
    makeCashbookForCashbookService(100.0);
    $request = approvedAdvanceForCashbookService(500.0);

    $this->expectException(InsufficientCashbookBalanceException::class);
    makeServiceForCashbookService()->recordDisbursement($request, $this->user);
});
test('record disbursement succeeds when balance exactly covers amount', function () {
    makeCashbookForCashbookService(500.0);
    $request = approvedAdvanceForCashbookService(500.0);

    makeServiceForCashbookService()->recordDisbursement($request, $this->user);

    $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
    expect((float) $cashbook->balance)->toEqualWithDelta(0.0, 0.01);
});
test('record disbursement dispatches balance changed event', function () {
    Event::fake([CashbookBalanceChanged::class]);
    makeCashbookForCashbookService(1000.0);
    $request = approvedAdvanceForCashbookService(300.0);

    makeServiceForCashbookService()->recordDisbursement($request, $this->user);

    Event::assertDispatched(CashbookBalanceChanged::class);
});
test('nil retirement creates no cashbook entry', function () {
    $retirement = approvedRetirementForCashbookService('nil', 0.0);

    makeServiceForCashbookService()->recordRetirementSettlement($retirement, $this->user);

    $this->assertDatabaseMissing('cashbook_entries', [
        'sourceable_type' => RetirementRequest::class,
        'sourceable_id' => $retirement->id,
    ]);
});
test('pay to staff retirement creates credit entry and decrements balance', function () {
    makeCashbookForCashbookService(1000.0);
    $retirement = approvedRetirementForCashbookService('pay_to_staff', 75.0);

    makeServiceForCashbookService()->recordRetirementSettlement($retirement, $this->user);

    $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'type' => 'credit',
        'amount' => '75.00',
        'sourceable_type' => RetirementRequest::class,
        'sourceable_id' => $retirement->id,
    ]);
    expect((float) $cashbook->balance)->toEqualWithDelta(925.0, 0.01);
});
test('refund to company retirement creates debit entry and increments balance', function () {
    makeCashbookForCashbookService(500.0);
    $retirement = approvedRetirementForCashbookService('refund_to_company', 100.0);

    makeServiceForCashbookService()->recordRetirementSettlement($retirement, $this->user);

    $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'amount' => '100.00',
        'sourceable_type' => RetirementRequest::class,
        'sourceable_id' => $retirement->id,
    ]);
    expect((float) $cashbook->balance)->toEqualWithDelta(600.0, 0.01);
});
test('pay to staff retirement throws when balance is positive but insufficient', function () {
    makeCashbookForCashbookService(50.0);
    $retirement = approvedRetirementForCashbookService('pay_to_staff', 200.0);

    $this->expectException(InsufficientCashbookBalanceException::class);
    makeServiceForCashbookService()->recordRetirementSettlement($retirement, $this->user);
});
test('retirement settlement dispatches balance changed event', function () {
    Event::fake([CashbookBalanceChanged::class]);
    makeCashbookForCashbookService(1000.0);
    $retirement = approvedRetirementForCashbookService('pay_to_staff', 50.0);

    makeServiceForCashbookService()->recordRetirementSettlement($retirement, $this->user);

    Event::assertDispatched(CashbookBalanceChanged::class);
});
test('record manual receipt creates debit entry and increments balance', function () {
    $cashbook = makeCashbookForCashbookService(200.0);
    $dto = new CashbookEntryDto(
        amount: '150.00',
        entryDate: today(),
        reference: 'REF-001',
        notes: 'Test receipt',
    );

    makeServiceForCashbookService()->recordManualReceipt($cashbook, $dto, $this->user);

    $this->assertDatabaseHas('cashbook_entries', [
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'amount' => '150.00',
        'reference' => 'REF-001',
    ]);
    expect((float) $cashbook->fresh()->balance)->toEqualWithDelta(350.0, 0.01);
});
test('record manual receipt dispatches balance changed event', function () {
    Event::fake([CashbookBalanceChanged::class]);
    $cashbook = makeCashbookForCashbookService(0.0);
    $dto = new CashbookEntryDto(amount: '100.00', entryDate: today(), reference: null, notes: null);

    makeServiceForCashbookService()->recordManualReceipt($cashbook, $dto, $this->user);

    Event::assertDispatched(CashbookBalanceChanged::class);
});
test('delete manual receipt decrements balance and removes entry', function () {
    $cashbook = makeCashbookForCashbookService(500.0);
    $entry = CashbookEntry::create([
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'amount' => 150.0,
        'description' => 'Manual receipt',
        'entry_date' => today(),
        'sourceable_type' => null,
        'sourceable_id' => null,
    ]);

    makeServiceForCashbookService()->deleteManualReceipt($entry);

    $this->assertSoftDeleted('cashbook_entries', ['id' => $entry->id]);
    expect((float) $cashbook->fresh()->balance)->toEqualWithDelta(350.0, 0.01);
});
test('delete manual receipt throws for auto generated entry', function () {
    $cashbook = makeCashbookForCashbookService(500.0);
    $request = approvedAdvanceForCashbookService(100.0);

    $entry = CashbookEntry::create([
        'cashbook_id' => $cashbook->id,
        'type' => 'credit',
        'amount' => 100.0,
        'description' => 'Payment disbursed',
        'entry_date' => today(),
        'sourceable_type' => PaymentRequest::class,
        'sourceable_id' => $request->id,
    ]);

    $this->expectException(LogicException::class);
    makeServiceForCashbookService()->deleteManualReceipt($entry);
});
test('delete manual receipt dispatches balance changed event', function () {
    Event::fake([CashbookBalanceChanged::class]);
    $cashbook = makeCashbookForCashbookService(300.0);
    $entry = CashbookEntry::create([
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'amount' => 100.0,
        'description' => 'Manual receipt',
        'entry_date' => today(),
        'sourceable_type' => null,
        'sourceable_id' => null,
    ]);

    makeServiceForCashbookService()->deleteManualReceipt($entry);

    Event::assertDispatched(CashbookBalanceChanged::class);
});
