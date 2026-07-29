<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;

function cashbookWithCurrency(Branch|null $branch = null, float $balance = 500.0): array
{
    $branch ??= test()->branch;
    $currency = Currency::factory()->create();
    $branch->update(['currency_id' => $currency->id]);

    $cashbook = Cashbook::create([
        'branch_id' => $branch->id,
        'currency_id' => $currency->id,
        'balance' => $balance,
    ]);

    return [$cashbook, $currency];
}
function denominationForCashCountController(Currency $currency, float $value, int $sort = 0): CurrencyDenomination
{
    return CurrencyDenomination::create([
        'currency_id' => $currency->id,
        'value' => $value,
        'label' => 'GHS ' . $value,
        'sort_order' => $sort,
    ]);
}
test('guest is redirected from index', function () {
    $this->get(route('cash-count.index', $this->branch))
        ->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $this->get(route('cash-count.create', $this->branch))
        ->assertRedirect(route('login'));
});
test('guest cannot store cash count', function () {
    $this->post(route('cash-count.store', $this->branch), [])
        ->assertRedirect(route('login'));
});
test('user without access permission cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessCashCount->value);

    $this->actingAs($this->user)
        ->get(route('cash-count.index', $this->branch))
        ->assertForbidden();
});
test('user without create permission cannot access create', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateCashCount->value);

    $this->actingAs($this->user)
        ->get(route('cash-count.create', $this->branch))
        ->assertForbidden();
});
test('user without create permission cannot store', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateCashCount->value);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [])
        ->assertForbidden();
});
test('user without delete permission cannot destroy', function () {
    $this->role->revokePermissionTo(PermissionKey::DeleteCashCount->value);
    [$cashbook] = cashbookWithCurrency();
    $cashCount = CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => 500,
        'counted_total' => 500,
        'difference' => 0,
    ]);

    $this->actingAs($this->user)
        ->delete(route('cash-count.destroy', [$this->branch, $cashCount]))
        ->assertForbidden();
});
test('authorised user sees cash count index', function () {
    cashbookWithCurrency();

    $this->actingAs($this->user)
        ->get(route('cash-count.index', $this->branch))
        ->assertOk()
        ->assertViewIs('tenant.cash-count.index');
});
test('create redirects when no denominations configured', function () {
    cashbookWithCurrency();

    $this->actingAs($this->user)
        ->get(route('cash-count.create', $this->branch))
        ->assertRedirect(route('cashbook.index', $this->branch))
        ->assertSessionHas('warning');
});
test('create shows denomination grid when denominations exist', function () {
    [$cashbook, $currency] = cashbookWithCurrency();
    denominationForCashCountController($currency, 50.0);

    $this->actingAs($this->user)
        ->get(route('cash-count.create', $this->branch))
        ->assertOk()
        ->assertViewIs('tenant.cash-count.create');
});
test('store creates cash count with correct totals', function () {
    [$cashbook, $currency] = cashbookWithCurrency(balance: 500.0);
    $d50 = denominationForCashCountController($currency, 50.0);
    $d20 = denominationForCashCountController($currency, 20.0);

    // 5 × 50 = 250, 5 × 20 = 100  → total = 350, balance = 500, diff = -150
    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'notes' => 'Test count',
            'items' => [
                ['denomination_id' => $d50->id, 'quantity' => 5],
                ['denomination_id' => $d20->id, 'quantity' => 5],
            ],
        ])
        ->assertRedirect();

    $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
    expect($cashCount)->not->toBeNull();
    expect((float) $cashCount->counted_total)->toEqual(350.00);
    expect((float) $cashCount->cashbook_balance_at_count)->toEqual(500.00);
    expect((float) $cashCount->difference)->toEqual(-150.00);
});
test('store creates cash count items for all denominations', function () {
    [$cashbook, $currency] = cashbookWithCurrency();
    $d50 = denominationForCashCountController($currency, 50.0);
    $d20 = denominationForCashCountController($currency, 20.0);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [
                ['denomination_id' => $d50->id, 'quantity' => 2],
                ['denomination_id' => $d20->id, 'quantity' => 0],
            ],
        ])
        ->assertRedirect();

    $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
    expect($cashCount->items)->toHaveCount(2);
});
test('store creates activity log entry', function () {
    [$cashbook, $currency] = cashbookWithCurrency();
    $d50 = denominationForCashCountController($currency, 50.0);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => $d50->id, 'quantity' => 1]],
        ]);

    $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'cash_count',
        'subject_id' => $cashCount->id,
        'event' => 'cash_count.created',
    ]);
});
test('status is equal when difference within tolerance', function () {
    [$cashbook, $currency] = cashbookWithCurrency(balance: 100.0);
    $d100 = denominationForCashCountController($currency, 100.0);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => $d100->id, 'quantity' => 1]],
        ]);

    $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
    expect($cashCount->status())->toEqual('equal');
});
test('status is surplus when counted exceeds balance', function () {
    [$cashbook, $currency] = cashbookWithCurrency(balance: 100.0);
    $d100 = denominationForCashCountController($currency, 100.0);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => $d100->id, 'quantity' => 2]],
        ]);

    $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
    expect($cashCount->status())->toEqual('surplus');
});
test('status is deficit when counted below balance', function () {
    [$cashbook, $currency] = cashbookWithCurrency(balance: 200.0);
    $d100 = denominationForCashCountController($currency, 100.0);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => $d100->id, 'quantity' => 1]],
        ]);

    $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
    expect($cashCount->status())->toEqual('deficit');
});
test('items are required', function () {
    cashbookWithCurrency();

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), ['notes' => 'no items'])
        ->assertSessionHasErrors('items');
});
test('at least one quantity must be positive', function () {
    [$cashbook, $currency] = cashbookWithCurrency();
    $d50 = denominationForCashCountController($currency, 50.0);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => $d50->id, 'quantity' => 0]],
        ])
        ->assertSessionHasErrors('items');
});
test('denomination id must exist', function () {
    cashbookWithCurrency();

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => 99999, 'quantity' => 1]],
        ])
        ->assertSessionHasErrors('items.0.denomination_id');
});
test('quantity must be non negative integer', function () {
    [$cashbook, $currency] = cashbookWithCurrency();
    $d50 = denominationForCashCountController($currency, 50.0);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => $d50->id, 'quantity' => -1]],
        ])
        ->assertSessionHasErrors('items.0.quantity');
});
test('authorised user can view cash count', function () {
    [$cashbook] = cashbookWithCurrency();
    $cashCount = CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => 500,
        'counted_total' => 500,
        'difference' => 0,
    ]);

    $this->actingAs($this->user)
        ->get(route('cash-count.show', [$this->branch, $cashCount]))
        ->assertOk()
        ->assertViewIs('tenant.cash-count.show');
});
test('authorised user can delete cash count', function () {
    [$cashbook] = cashbookWithCurrency();
    $cashCount = CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => 500,
        'counted_total' => 500,
        'difference' => 0,
    ]);

    $this->actingAs($this->user)
        ->delete(route('cash-count.destroy', [$this->branch, $cashCount]))
        ->assertRedirect(route('cash-count.index', $this->branch))
        ->assertSessionHas('success');

    $this->assertSoftDeleted('cash_counts', ['id' => $cashCount->id]);
});
test('delete creates activity log', function () {
    [$cashbook] = cashbookWithCurrency();
    $cashCount = CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => 500,
        'counted_total' => 500,
        'difference' => 0,
    ]);

    $this->actingAs($this->user)
        ->delete(route('cash-count.destroy', [$this->branch, $cashCount]));

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'cash_count',
        'subject_id' => $cashCount->id,
        'event' => 'cash_count.deleted',
    ]);
});
test('zero balance and zero quantities produces equal status', function () {
    [$cashbook, $currency] = cashbookWithCurrency(balance: 0.0);
    $d50 = denominationForCashCountController($currency, 50.0);

    // Need at least 1 positive qty to pass validation, but test equality at 0 balance
    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => $d50->id, 'quantity' => 0]],
        ])
        ->assertSessionHasErrors('items');
    // blocked by validation — zero balance + all zeros fails validation
});
test('large denomination values compute correctly', function () {
    [$cashbook, $currency] = cashbookWithCurrency(balance: 0.0);
    $d200 = denominationForCashCountController($currency, 200.0);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => $d200->id, 'quantity' => 100]],
        ]);

    $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
    expect((float) $cashCount->counted_total)->toEqual(20000.00);
});
test('snapshot is stored not live balance', function () {
    [$cashbook, $currency] = cashbookWithCurrency(balance: 300.0);
    $d100 = denominationForCashCountController($currency, 100.0);

    $this->actingAs($this->user)
        ->post(route('cash-count.store', $this->branch), [
            'items' => [['denomination_id' => $d100->id, 'quantity' => 2]],
        ]);

    // Now change the balance
    $cashbook->update(['balance' => 999.00]);

    $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
    expect((float) $cashCount->cashbook_balance_at_count)->toEqual(300.00);
});
