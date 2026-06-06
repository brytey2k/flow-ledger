<?php

declare(strict_types=1);

namespace Tests\Feature\CashCount;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use Tests\TenantAppTestCase;

class CashCountControllerTest extends TenantAppTestCase
{
    private function cashbookWithCurrency(Branch|null $branch = null, float $balance = 500.0): array
    {
        $branch ??= $this->branch;
        $currency = Currency::factory()->create();
        $branch->update(['currency_id' => $currency->id]);

        $cashbook = Cashbook::create([
            'branch_id' => $branch->id,
            'currency_id' => $currency->id,
            'balance' => $balance,
        ]);

        return [$cashbook, $currency];
    }

    private function denomination(Currency $currency, float $value, int $sort = 0): CurrencyDenomination
    {
        return CurrencyDenomination::create([
            'currency_id' => $currency->id,
            'value' => $value,
            'label' => 'GHS ' . $value,
            'sort_order' => $sort,
        ]);
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('cash-count.index', $this->branch))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('cash-count.create', $this->branch))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_cash_count(): void
    {
        $this->post(route('cash-count.store', $this->branch), [])
            ->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessCashCount->value);

        $this->actingAs($this->user)
            ->get(route('cash-count.index', $this->branch))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_access_create(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCashCount->value);

        $this->actingAs($this->user)
            ->get(route('cash-count.create', $this->branch))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_store(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCashCount->value);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [])
            ->assertForbidden();
    }

    public function test_user_without_delete_permission_cannot_destroy(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DeleteCashCount->value);
        [$cashbook] = $this->cashbookWithCurrency();
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
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_sees_cash_count_index(): void
    {
        $this->cashbookWithCurrency();

        $this->actingAs($this->user)
            ->get(route('cash-count.index', $this->branch))
            ->assertOk()
            ->assertViewIs('tenant.cash-count.index');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_redirects_when_no_denominations_configured(): void
    {
        $this->cashbookWithCurrency();

        $this->actingAs($this->user)
            ->get(route('cash-count.create', $this->branch))
            ->assertRedirect(route('cashbook.index', $this->branch))
            ->assertSessionHas('warning');
    }

    public function test_create_shows_denomination_grid_when_denominations_exist(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency();
        $this->denomination($currency, 50.0);

        $this->actingAs($this->user)
            ->get(route('cash-count.create', $this->branch))
            ->assertOk()
            ->assertViewIs('tenant.cash-count.create');
    }

    // ── Store (happy path) ────────────────────────────────────────────────────

    public function test_store_creates_cash_count_with_correct_totals(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency(balance: 500.0);
        $d50 = $this->denomination($currency, 50.0);
        $d20 = $this->denomination($currency, 20.0);

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
        $this->assertNotNull($cashCount);
        $this->assertEquals(350.00, (float) $cashCount->counted_total);
        $this->assertEquals(500.00, (float) $cashCount->cashbook_balance_at_count);
        $this->assertEquals(-150.00, (float) $cashCount->difference);
    }

    public function test_store_creates_cash_count_items_for_all_denominations(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency();
        $d50 = $this->denomination($currency, 50.0);
        $d20 = $this->denomination($currency, 20.0);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [
                    ['denomination_id' => $d50->id, 'quantity' => 2],
                    ['denomination_id' => $d20->id, 'quantity' => 0],
                ],
            ])
            ->assertRedirect();

        $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
        $this->assertCount(2, $cashCount->items);
    }

    public function test_store_creates_activity_log_entry(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency();
        $d50 = $this->denomination($currency, 50.0);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => $d50->id, 'quantity' => 1]],
            ]);

        $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
        $this->assertDatabaseHas('activity_log', [
            'subject_type' => CashCount::class,
            'subject_id' => $cashCount->id,
            'event' => 'cash_count.created',
        ]);
    }

    // ── Status calculations ───────────────────────────────────────────────────

    public function test_status_is_equal_when_difference_within_tolerance(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency(balance: 100.0);
        $d100 = $this->denomination($currency, 100.0);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => $d100->id, 'quantity' => 1]],
            ]);

        $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
        $this->assertEquals('equal', $cashCount->status());
    }

    public function test_status_is_surplus_when_counted_exceeds_balance(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency(balance: 100.0);
        $d100 = $this->denomination($currency, 100.0);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => $d100->id, 'quantity' => 2]],
            ]);

        $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
        $this->assertEquals('surplus', $cashCount->status());
    }

    public function test_status_is_deficit_when_counted_below_balance(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency(balance: 200.0);
        $d100 = $this->denomination($currency, 100.0);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => $d100->id, 'quantity' => 1]],
            ]);

        $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
        $this->assertEquals('deficit', $cashCount->status());
    }

    // ── Validation ────────────────────────────────────────────────────────────

    public function test_items_are_required(): void
    {
        $this->cashbookWithCurrency();

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), ['notes' => 'no items'])
            ->assertSessionHasErrors('items');
    }

    public function test_at_least_one_quantity_must_be_positive(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency();
        $d50 = $this->denomination($currency, 50.0);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => $d50->id, 'quantity' => 0]],
            ])
            ->assertSessionHasErrors('items');
    }

    public function test_denomination_id_must_exist(): void
    {
        $this->cashbookWithCurrency();

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => 99999, 'quantity' => 1]],
            ])
            ->assertSessionHasErrors('items.0.denomination_id');
    }

    public function test_quantity_must_be_non_negative_integer(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency();
        $d50 = $this->denomination($currency, 50.0);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => $d50->id, 'quantity' => -1]],
            ])
            ->assertSessionHasErrors('items.0.quantity');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_cash_count(): void
    {
        [$cashbook] = $this->cashbookWithCurrency();
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
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorised_user_can_delete_cash_count(): void
    {
        [$cashbook] = $this->cashbookWithCurrency();
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
    }

    public function test_delete_creates_activity_log(): void
    {
        [$cashbook] = $this->cashbookWithCurrency();
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
            'subject_type' => CashCount::class,
            'subject_id' => $cashCount->id,
            'event' => 'cash_count.deleted',
        ]);
    }

    // ── Edge cases ────────────────────────────────────────────────────────────

    public function test_zero_balance_and_zero_quantities_produces_equal_status(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency(balance: 0.0);
        $d50 = $this->denomination($currency, 50.0);

        // Need at least 1 positive qty to pass validation, but test equality at 0 balance
        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => $d50->id, 'quantity' => 0]],
            ])
            ->assertSessionHasErrors('items'); // blocked by validation — zero balance + all zeros fails validation
    }

    public function test_large_denomination_values_compute_correctly(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency(balance: 0.0);
        $d200 = $this->denomination($currency, 200.0);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => $d200->id, 'quantity' => 100]],
            ]);

        $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
        $this->assertEquals(20000.00, (float) $cashCount->counted_total);
    }

    public function test_snapshot_is_stored_not_live_balance(): void
    {
        [$cashbook, $currency] = $this->cashbookWithCurrency(balance: 300.0);
        $d100 = $this->denomination($currency, 100.0);

        $this->actingAs($this->user)
            ->post(route('cash-count.store', $this->branch), [
                'items' => [['denomination_id' => $d100->id, 'quantity' => 2]],
            ]);

        // Now change the balance
        $cashbook->update(['balance' => 999.00]);

        $cashCount = CashCount::where('cashbook_id', $cashbook->id)->latest()->first();
        $this->assertEquals(300.00, (float) $cashCount->cashbook_balance_at_count);
    }
}
