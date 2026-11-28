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
function makeCashbookForCashbookService(float|string $balance = 0.0): Cashbook
{
    $currency = Currency::factory()->create();

    return Cashbook::create([
        'branch_id' => test()->branch->id,
        'currency_id' => $currency->id,
        'balance' => $balance,
    ]);
}
function approvedAdvanceForCashbookService(float|string $amount = 500.0): PaymentRequest
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
    makeCashbookForCashbookService(300.0);
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
test('record disbursement throws when balance is zero', function () {
    makeCashbookForCashbookService(0.0);
    $request = approvedAdvanceForCashbookService(500.0);

    $this->expectException(InsufficientCashbookBalanceException::class);
    makeServiceForCashbookService()->recordDisbursement($request, $this->user);
});
test('record disbursement auto creates cashbook when none exists', function () {
    expect(Cashbook::where('branch_id', $this->branch->id)->first())->toBeNull();
    $request = approvedAdvanceForCashbookService(200.0);

    // The new cashbook starts at zero, so the payout itself is refused until cash is receipted in.
    expect(fn() => makeServiceForCashbookService()->recordDisbursement($request, $this->user))
        ->toThrow(InsufficientCashbookBalanceException::class);
    expect((float) Cashbook::where('branch_id', $this->branch->id)->firstOrFail()->balance)->toEqualWithDelta(0.0, 0.01);
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
test('pay to staff retirement throws when balance is zero', function () {
    makeCashbookForCashbookService(0.0);
    $retirement = approvedRetirementForCashbookService('pay_to_staff', 200.0);

    $this->expectException(InsufficientCashbookBalanceException::class);
    makeServiceForCashbookService()->recordRetirementSettlement($retirement, $this->user);
});
test('pay to staff retirement throws when balance is negative', function () {
    makeCashbookForCashbookService(-50.0);
    $retirement = approvedRetirementForCashbookService('pay_to_staff', 200.0);

    $this->expectException(InsufficientCashbookBalanceException::class);
    makeServiceForCashbookService()->recordRetirementSettlement($retirement, $this->user);
});
test('refund to company retirement is accepted when balance is zero', function () {
    makeCashbookForCashbookService(0.0);
    $retirement = approvedRetirementForCashbookService('refund_to_company', 100.0);

    makeServiceForCashbookService()->recordRetirementSettlement($retirement, $this->user);

    expect((float) Cashbook::where('branch_id', $this->branch->id)->firstOrFail()->balance)->toEqualWithDelta(100.0, 0.01);
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
test('the database refuses a second entry of the same type for one source', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'total_amount' => 50.0]);
    $cashbook = Cashbook::create([
        'branch_id' => $this->branch->id,
        'currency_id' => Currency::factory()->create()->id,
        'balance' => 0,
    ]);
    $entry = fn(string $type): array => [
        'cashbook_id' => $cashbook->id, 'type' => $type, 'amount' => 50, 'description' => 'x', 'entry_date' => today(),
        'sourceable_type' => PaymentRequest::class, 'sourceable_id' => $request->id,
    ];

    $first = CashbookEntry::create($entry('credit'));

    expect(fn() => CashbookEntry::create($entry('credit')))->toThrow(Illuminate\Database\QueryException::class);
});
test('a balance of 0.30 covers a payout of 0.30 and leaves exactly zero', function () {
    Event::fake([CashbookBalanceChanged::class]);
    $cashbook = makeCashbookForCashbookService('0.30');
    $request = approvedAdvanceForCashbookService('0.30');

    makeServiceForCashbookService()->recordDisbursement($request, $this->user);

    expect($cashbook->fresh()->balance)->toBe('0.00');
    Event::assertDispatched(CashbookBalanceChanged::class, fn(CashbookBalanceChanged $event): bool => $event->previousBalance === '0.30' && $event->newBalance === '0.00');
});
test('balance changes stay exact at the largest amounts a cashbook holds', function () {
    Event::fake([CashbookBalanceChanged::class]);
    $cashbook = makeCashbookForCashbookService('1234567890123.45');

    makeServiceForCashbookService()->recordManualReceipt($cashbook, new CashbookEntryDto(amount: '0.01', entryDate: today(), reference: null, notes: null), $this->user);

    expect($cashbook->fresh()->balance)->toBe('1234567890123.46');
    Event::assertDispatched(CashbookBalanceChanged::class, fn(CashbookBalanceChanged $event): bool => $event->previousBalance === '1234567890123.45' && $event->newBalance === '1234567890123.46');
});
test('a receipt moves the stored balance, not a stale copy of it', function () {
    Event::fake([CashbookBalanceChanged::class]);
    $staleCopy = makeCashbookForCashbookService('500.00');
    // Another movement commits after this copy was loaded.
    Cashbook::whereKey($staleCopy->id)->update(['balance' => '800.00']);

    makeServiceForCashbookService()->recordManualReceipt($staleCopy, new CashbookEntryDto(amount: '100.00', entryDate: today(), reference: null, notes: null), $this->user);

    expect(Cashbook::findOrFail($staleCopy->id)->balance)->toBe('900.00');
    Event::assertDispatched(CashbookBalanceChanged::class, fn(CashbookBalanceChanged $event): bool => $event->previousBalance === '800.00' && $event->newBalance === '900.00');
});
test('deleting a receipt takes it off the stored balance, not a stale copy of it', function () {
    $cashbook = makeCashbookForCashbookService('0.00');
    $entry = makeServiceForCashbookService()->recordManualReceipt($cashbook, new CashbookEntryDto(amount: '250.00', entryDate: today(), reference: null, notes: null), $this->user);
    $entry->load('cashbook');
    Cashbook::whereKey($cashbook->id)->update(['balance' => '400.00']);

    makeServiceForCashbookService()->deleteManualReceipt($entry);

    expect($cashbook->fresh()->balance)->toBe('150.00');
});
