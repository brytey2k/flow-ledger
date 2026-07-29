<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Currency;

test('guest is redirected from index', function () {
    $response = $this->get(route('currencies.index'));

    $response->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $response = $this->get(route('currencies.create'));

    $response->assertRedirect(route('login'));
});
test('guest cannot post to store', function () {
    $response = $this->post(route('currencies.store'), [
        'name' => 'US Dollar',
        'short_name' => 'USD',
        'symbol' => '$',
    ]);

    $response->assertRedirect(route('login'));
});
test('user without access permission cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessCurrencies->value);

    $response = $this->actingAs($this->user)->get(route('currencies.index'));

    $response->assertForbidden();
});
test('user without create permission cannot access create form', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateCurrency->value);

    $response = $this->actingAs($this->user)->get(route('currencies.create'));

    $response->assertForbidden();
});
test('authorised user can view index', function () {
    $response = $this->actingAs($this->user)->get(route('currencies.index'));

    $response->assertOk();
});
test('authorised user can view create form', function () {
    $response = $this->actingAs($this->user)->get(route('currencies.create'));

    $response->assertOk();
});
test('authorised user can store currency', function () {
    $response = $this->actingAs($this->user)->post(route('currencies.store'), [
        'name' => 'US Dollar',
        'short_name' => 'USD',
        'symbol' => '$',
    ]);

    $response->assertRedirect(route('currencies.index'));
    $this->assertDatabaseHas('currencies', ['name' => 'US Dollar', 'short_name' => 'USD', 'symbol' => '$']);
});
test('store fails validation for missing required fields', function () {
    $response = $this->actingAs($this->user)->post(route('currencies.store'), []);

    $response->assertSessionHasErrors(['name', 'short_name', 'symbol']);
});
test('authorised user can view edit form', function () {
    $currency = Currency::factory()->create();

    $response = $this->actingAs($this->user)->get(route('currencies.edit', $currency));

    $response->assertOk();
});
test('authorised user can update currency', function () {
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
});
test('authorised user can delete currency', function () {
    $currency = Currency::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('currencies.destroy', $currency));

    $response->assertRedirect(route('currencies.index'));
    $this->assertSoftDeleted('currencies', ['id' => $currency->id]);
});
