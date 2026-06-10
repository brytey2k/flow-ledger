<?php

declare(strict_types=1);

namespace Tests\Feature\CurrencyDenominations;

use App\Enums\Tenant\CurrencyDenominationType;
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use Tests\TenantAppTestCase;

class CurrencyDenominationsControllerTest extends TenantAppTestCase
{
    private function currency(): Currency
    {
        return Currency::factory()->create();
    }

    private function denomination(Currency $currency, float $value = 5.0, CurrencyDenominationType $type = CurrencyDenominationType::Note): CurrencyDenomination
    {
        return CurrencyDenomination::create([
            'currency_id' => $currency->id,
            'value' => $value,
            'label' => 'GHS ' . $value,
            'type' => $type,
            'sort_order' => 0,
        ]);
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $currency = $this->currency();

        $this->get(route('currency.denominations.index', $currency))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_create(): void
    {
        $currency = $this->currency();

        $this->get(route('currency.denominations.create', $currency))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_denomination(): void
    {
        $currency = $this->currency();

        $this->post(route('currency.denominations.store', $currency), [])
            ->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::ManageCurrencyDenominations->value);
        $currency = $this->currency();

        $this->actingAs($this->user)
            ->get(route('currency.denominations.index', $currency))
            ->assertForbidden();
    }

    public function test_user_without_permission_cannot_store(): void
    {
        $this->role->revokePermissionTo(PermissionKey::ManageCurrencyDenominations->value);
        $currency = $this->currency();

        $this->actingAs($this->user)
            ->post(route('currency.denominations.store', $currency), ['value' => '10', 'label' => 'GHS 10'])
            ->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_sees_denominations_index(): void
    {
        $currency = $this->currency();
        $this->denomination($currency, 10.0);

        $this->actingAs($this->user)
            ->get(route('currency.denominations.index', $currency))
            ->assertOk()
            ->assertViewIs('tenant.currencies.denominations.index');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_create_denomination(): void
    {
        $currency = $this->currency();

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
    }

    public function test_value_is_required_for_store(): void
    {
        $currency = $this->currency();

        $this->actingAs($this->user)
            ->post(route('currency.denominations.store', $currency), ['label' => 'GHS 50'])
            ->assertSessionHasErrors('value');
    }

    public function test_label_is_required_for_store(): void
    {
        $currency = $this->currency();

        $this->actingAs($this->user)
            ->post(route('currency.denominations.store', $currency), ['value' => '50'])
            ->assertSessionHasErrors('label');
    }

    public function test_label_max_100_chars(): void
    {
        $currency = $this->currency();

        $this->actingAs($this->user)
            ->post(route('currency.denominations.store', $currency), [
                'value' => '50',
                'label' => str_repeat('a', 101),
            ])
            ->assertSessionHasErrors('label');
    }

    public function test_duplicate_value_for_same_currency_is_rejected(): void
    {
        $currency = $this->currency();
        $this->denomination($currency, 20.0);

        $this->actingAs($this->user)
            ->post(route('currency.denominations.store', $currency), [
                'value' => '20.0000',
                'label' => 'GHS 20 duplicate',
            ])
            ->assertSessionHasErrors('value');
    }

    public function test_same_value_different_type_for_same_currency_is_allowed(): void
    {
        $currency = $this->currency();
        $this->denomination($currency, 1.0, CurrencyDenominationType::Note);

        $this->actingAs($this->user)
            ->post(route('currency.denominations.store', $currency), [
                'value' => '1.0000',
                'label' => '1 Coin',
                'type' => 'coin',
            ])
            ->assertRedirect(route('currency.denominations.index', $currency));
    }

    public function test_same_value_for_different_currency_is_allowed(): void
    {
        $currencyA = $this->currency();
        $currencyB = $this->currency();
        $this->denomination($currencyA, 20.0);

        $this->actingAs($this->user)
            ->post(route('currency.denominations.store', $currencyB), [
                'value' => '20',
                'label' => 'USD 20',
                'type' => 'note',
            ])
            ->assertRedirect(route('currency.denominations.index', $currencyB));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_update_denomination(): void
    {
        $currency = $this->currency();
        $denomination = $this->denomination($currency, 5.0);

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
    }

    public function test_update_unique_rule_ignores_current_record(): void
    {
        $currency = $this->currency();
        $denomination = $this->denomination($currency, 5.0);

        $this->actingAs($this->user)
            ->put(route('currency.denominations.update', [$currency, $denomination]), [
                'value' => '5.0000',
                'label' => 'Same value, new label',
                'type' => 'note',
            ])
            ->assertRedirect(route('currency.denominations.index', $currency));
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorised_user_can_delete_unused_denomination(): void
    {
        $currency = $this->currency();
        $denomination = $this->denomination($currency, 100.0);

        $this->actingAs($this->user)
            ->delete(route('currency.denominations.destroy', [$currency, $denomination]))
            ->assertRedirect(route('currency.denominations.index', $currency))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('currency_denominations', ['id' => $denomination->id]);
    }

    public function test_denomination_in_use_cannot_be_deleted(): void
    {
        $currency = $this->currency();
        $denomination = $this->denomination($currency, 50.0);

        // Create a cash count item referencing this denomination
        $cashbook = \App\Models\Tenant\Cashbook::create([
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'balance' => 0,
        ]);
        $cashCount = \App\Models\Tenant\CashCount::create([
            'cashbook_id' => $cashbook->id,
            'counted_by_user_id' => $this->user->id,
            'counted_at' => now(),
            'cashbook_balance_at_count' => 0,
            'counted_total' => 50,
            'difference' => 50,
        ]);
        \App\Models\Tenant\CashCountItem::create([
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
    }
}
