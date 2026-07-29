<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\DTOs\Tenant\CashCountDto;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\CashCountItem;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use App\Services\CashCountService;

beforeEach(function () {
    $this->service = app(CashCountService::class);

    $this->currency = Currency::factory()->create();

    $this->cashbook = Cashbook::create([
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'balance' => '1000.00',
    ]);
});
test('store returns cash count instance', function () {
    $denomination = makeDenominationForCashCountService('10.00', '10');
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [['denomination_id' => $denomination->id, 'quantity' => 5]],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect($cashCount)->toBeInstanceOf(CashCount::class);
});
test('store persists cash count to database', function () {
    $denomination = makeDenominationForCashCountService('10.00', '10');
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [['denomination_id' => $denomination->id, 'quantity' => 5]],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect(CashCount::find($cashCount->id))->not->toBeNull();
});
test('store calculates counted total correctly', function () {
    // 3 × 10.00 + 2 × 50.00 = 130.00
    $ten = makeDenominationForCashCountService('10.00', '10');
    $fifty = makeDenominationForCashCountService('50.00', '50');
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [
            ['denomination_id' => $ten->id, 'quantity' => 3],
            ['denomination_id' => $fifty->id, 'quantity' => 2],
        ],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect((float) $cashCount->counted_total)->toEqualWithDelta(130.00, 0.001);
});
test('store computes difference as counted total minus balance', function () {
    // balance = 1000.00, counted = 5 × 10.00 = 50.00 → difference = -950.00
    $denomination = makeDenominationForCashCountService('10.00', '10');
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [['denomination_id' => $denomination->id, 'quantity' => 5]],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect((float) $cashCount->difference)->toEqualWithDelta(-950.00, 0.001);
});
test('store creates cash count item records for each denomination', function () {
    $ten = makeDenominationForCashCountService('10.00', '10');
    $fifty = makeDenominationForCashCountService('50.00', '50');
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [
            ['denomination_id' => $ten->id, 'quantity' => 2],
            ['denomination_id' => $fifty->id, 'quantity' => 3],
        ],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    $items = CashCountItem::where('cash_count_id', $cashCount->id)->get();
    expect($items)->toHaveCount(2);
});
test('store cash count item has correct subtotal', function () {
    // 4 × 25.00 = 100.00
    $denomination = makeDenominationForCashCountService('25.00', '25');
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [['denomination_id' => $denomination->id, 'quantity' => 4]],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    $item = CashCountItem::where('cash_count_id', $cashCount->id)->first();
    expect($item)->not->toBeNull();
    expect((float) $item->subtotal)->toEqualWithDelta(100.00, 0.001);
});
test('store records cashbook balance at count', function () {
    $denomination = makeDenominationForCashCountService('10.00', '10');
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [['denomination_id' => $denomination->id, 'quantity' => 1]],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect((float) $cashCount->cashbook_balance_at_count)->toEqualWithDelta(1000.00, 0.001);
});
test('store links counted by user', function () {
    $denomination = makeDenominationForCashCountService('10.00', '10');
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [['denomination_id' => $denomination->id, 'quantity' => 1]],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect($cashCount->counted_by_user_id)->toBe($this->user->id);
});
test('store saves notes', function () {
    $denomination = makeDenominationForCashCountService('10.00', '10');
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: 'End of day count',
        items: [['denomination_id' => $denomination->id, 'quantity' => 1]],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect($cashCount->notes)->toBe('End of day count');
});
test('store with empty items sets counted total to zero', function () {
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect((float) $cashCount->counted_total)->toEqualWithDelta(0.00, 0.001);
});
test('store with empty items sets difference to negative balance', function () {
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect((float) $cashCount->difference)->toEqualWithDelta(-1000.00, 0.001);
});
test('store with empty items creates no cash count item records', function () {
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    $count = CashCountItem::where('cash_count_id', $cashCount->id)->count();
    expect($count)->toBe(0);
});
test('store skips items with unknown denomination id', function () {
    $dto = new CashCountDto(
        cashbookId: $this->cashbook->id,
        notes: null,
        items: [['denomination_id' => 999999, 'quantity' => 10]],
    );

    $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

    expect((float) $cashCount->counted_total)->toEqualWithDelta(0.00, 0.001);
    $count = CashCountItem::where('cash_count_id', $cashCount->id)->count();
    expect($count)->toBe(0);
});
test('delete removes cash count', function () {
    $cashCount = makeCashCount();
    $cashCountId = $cashCount->id;

    $this->service->delete($cashCount, $this->user);

    expect(CashCount::find($cashCountId))->toBeNull();
});
test('delete soft deletes the record', function () {
    $cashCount = makeCashCount();
    $cashCountId = $cashCount->id;

    $this->service->delete($cashCount, $this->user);

    expect(CashCount::withTrashed()->find($cashCountId))->not->toBeNull();
});
// ── Helpers ───────────────────────────────────────────────────────────────
function makeCashCount(): CashCount
{
    return CashCount::create([
        'cashbook_id' => test()->cashbook->id,
        'counted_by_user_id' => test()->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => '1000.00',
        'counted_total' => '1000.00',
        'difference' => '0.00',
        'notes' => null,
    ]);
}
function makeDenominationForCashCountService(string $value, string $label, int $sortOrder = 10): CurrencyDenomination
{
    return CurrencyDenomination::create([
        'currency_id' => test()->currency->id,
        'value' => $value,
        'label' => $label,
        'type' => 'note',
        'sort_order' => $sortOrder,
    ]);
}
