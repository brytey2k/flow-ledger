<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\CurrencyDenominationType;
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;

function currency(): Currency
{
    return Currency::factory()->create();
}
function denominationForCurrencyDenominationsController(Currency $currency, float $value = 5.0, CurrencyDenominationType $type = CurrencyDenominationType::Note): CurrencyDenomination
{
    return CurrencyDenomination::create([
        'currency_id' => $currency->id,
        'value' => $value,
        'label' => 'GHS ' . $value,
        'type' => $type,
        'sort_order' => 0,
    ]);
}
test('guest is redirected from index', function () {
    $currency = currency();

    $this->get(route('currency.denominations.index', $currency))
        ->assertRedirect(route('login'));
});
test('guest cannot access create', function () {
    $currency = currency();

    $this->get(route('currency.denominations.create', $currency))
        ->assertRedirect(route('login'));
});
test('guest cannot store denomination', function () {
    $currency = currency();

    $this->post(route('currency.denominations.store', $currency), [])
        ->assertRedirect(route('login'));
});
test('user without permission cannot access index', function () {
    $this->role->revokePermissionTo(PermissionKey::ManageCurrencyDenominations->value);
    $currency = currency();

    $this->actingAs($this->user)
        ->get(route('currency.denominations.index', $currency))
        ->assertForbidden();
});
test('user without permission cannot store', function () {
    $this->role->revokePermissionTo(PermissionKey::ManageCurrencyDenominations->value);
    $currency = currency();

    $this->actingAs($this->user)
        ->post(route('currency.denominations.store', $currency), ['value' => '10', 'label' => 'GHS 10'])
        ->assertForbidden();
});
test('authorised user sees denominations index', function () {
    $currency = currency();
    denominationForCurrencyDenominationsController($currency, 10.0);

    $this->actingAs($this->user)
        ->get(route('currency.denominations.index', $currency))
        ->assertOk()
        ->assertViewIs('tenant.currencies.denominations.index');
});
test('authorised user can create denomination', function () {
    $currency = currency();

    $this->actingAs($this->user)
        ->post(route('currency.denominations.store', $currency), [
            'value' => '50.00',
            'label' => 'GHS 50',
            'type' => 'note',
        ])
        ->assertRedirect(route('currency.denominations.index', $currency))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('currency_denominations', [
        'currency_id' => $currency->id,
        'label' => 'GHS 50',
    ]);
});
test('value is required for store', function () {
    $currency = currency();

    $this->actingAs($this->user)
        ->post(route('currency.denominations.store', $currency), ['label' => 'GHS 50'])
        ->assertSessionHasErrors('value');
});
test('label is required for store', function () {
    $currency = currency();

    $this->actingAs($this->user)
        ->post(route('currency.denominations.store', $currency), ['value' => '50'])
        ->assertSessionHasErrors('label');
});
test('label max 100 chars', function () {
    $currency = currency();

    $this->actingAs($this->user)
        ->post(route('currency.denominations.store', $currency), [
            'value' => '50',
            'label' => str_repeat('a', 101),
        ])
        ->assertSessionHasErrors('label');
});
test('duplicate value for same currency is rejected', function () {
    $currency = currency();
    denominationForCurrencyDenominationsController($currency, 20.0);

    $this->actingAs($this->user)
        ->post(route('currency.denominations.store', $currency), [
            'value' => '20.0000',
            'label' => 'GHS 20 duplicate',
        ])
        ->assertSessionHasErrors('value');
});
test('same value different type for same currency is allowed', function () {
    $currency = currency();
    denominationForCurrencyDenominationsController($currency, 1.0, CurrencyDenominationType::Note);

    $this->actingAs($this->user)
        ->post(route('currency.denominations.store', $currency), [
            'value' => '1.0000',
            'label' => '1 Coin',
            'type' => 'coin',
        ])
        ->assertRedirect(route('currency.denominations.index', $currency));
});
test('same value for different currency is allowed', function () {
    $currencyA = currency();
    $currencyB = currency();
    denominationForCurrencyDenominationsController($currencyA, 20.0);

    $this->actingAs($this->user)
        ->post(route('currency.denominations.store', $currencyB), [
            'value' => '20',
            'label' => 'USD 20',
            'type' => 'note',
        ])
        ->assertRedirect(route('currency.denominations.index', $currencyB));
});
test('authorised user can update denomination', function () {
    $currency = currency();
    $denomination = denominationForCurrencyDenominationsController($currency, 5.0);

    $this->actingAs($this->user)
        ->put(route('currency.denominations.update', [$currency, $denomination]), [
            'value' => '5.0000',
            'label' => 'Updated Label',
            'type' => 'note',
            'sort_order' => 1,
        ])
        ->assertRedirect(route('currency.denominations.index', $currency))
        ->assertSessionHas('success');

    $this->assertDatabaseHas('currency_denominations', [
        'id' => $denomination->id,
        'label' => 'Updated Label',
    ]);
});
test('update unique rule ignores current record', function () {
    $currency = currency();
    $denomination = denominationForCurrencyDenominationsController($currency, 5.0);

    $this->actingAs($this->user)
        ->put(route('currency.denominations.update', [$currency, $denomination]), [
            'value' => '5.0000',
            'label' => 'Same value, new label',
            'type' => 'note',
        ])
        ->assertRedirect(route('currency.denominations.index', $currency));
});
test('authorised user can delete unused denomination', function () {
    $currency = currency();
    $denomination = denominationForCurrencyDenominationsController($currency, 100.0);

    $this->actingAs($this->user)
        ->delete(route('currency.denominations.destroy', [$currency, $denomination]))
        ->assertRedirect(route('currency.denominations.index', $currency))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('currency_denominations', ['id' => $denomination->id]);
});
test('denomination in use cannot be deleted', function () {
    $currency = currency();
    $denomination = denominationForCurrencyDenominationsController($currency, 50.0);

    // Create a cash count item referencing this denomination
    $cashbook = App\Models\Tenant\Cashbook::create([
        'branch_id' => $this->branch->id,
        'currency_id' => $currency->id,
        'balance' => 0,
    ]);
    $cashCount = App\Models\Tenant\CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => 0,
        'counted_total' => 50,
        'difference' => 50,
    ]);
    App\Models\Tenant\CashCountItem::create([
        'cash_count_id' => $cashCount->id,
        'denomination_id' => $denomination->id,
        'denomination_value' => $denomination->value,
        'denomination_label' => $denomination->label,
        'quantity' => 1,
        'subtotal' => 50,
    ]);

    $this->actingAs($this->user)
        ->delete(route('currency.denominations.destroy', [$currency, $denomination]))
        ->assertRedirect(route('currency.denominations.index', $currency))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('currency_denominations', ['id' => $denomination->id]);
});
