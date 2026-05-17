<?php

declare(strict_types=1);

namespace Tests\Feature\Currencies;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Currency;
use Tests\TenantAppTestCase;

class CurrenciesControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('currencies.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $response = $this->get(route('currencies.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_post_to_store(): void
    {
        $response = $this->post(route('currencies.store'), [
            'name' => 'US Dollar',
            'short_name' => 'USD',
            'symbol' => '$',
        ]);

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessCurrencies->value);

        $response = $this->actingAs($this->user)->get(route('currencies.index'));

        $response->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_access_create_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCurrency->value);

        $response = $this->actingAs($this->user)->get(route('currencies.create'));

        $response->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('currencies.index'));

        $response->assertOk();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('currencies.create'));

        $response->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_store_currency(): void
    {
        $response = $this->actingAs($this->user)->post(route('currencies.store'), [
            'name' => 'US Dollar',
            'short_name' => 'USD',
            'symbol' => '$',
        ]);

        $response->assertRedirect(route('currencies.index'));
        $this->assertDatabaseHas('currencies', ['name' => 'US Dollar', 'short_name' => 'USD', 'symbol' => '$']);
    }

    public function test_store_fails_validation_for_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('currencies.store'), []);

        $response->assertSessionHasErrors(['name', 'short_name', 'symbol']);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_edit_form(): void
    {
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->user)->get(route('currencies.edit', $currency));

        $response->assertOk();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_update_currency(): void
    {
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->user)->put(route('currencies.update', $currency), [
            'name' => 'Euro',
            'short_name' => 'EUR',
            'symbol' => '€',
        ]);

        $response->assertRedirect(route('currencies.index'));
        $this->assertDatabaseHas('currencies', [
            'id' => $currency->id,
            'name' => 'Euro',
            'short_name' => 'EUR',
            'symbol' => '€',
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorised_user_can_delete_currency(): void
    {
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('currencies.destroy', $currency));

        $response->assertRedirect(route('currencies.index'));
        $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
    }
}
