<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Enums\FeatureFlag;

test('overview renders for authenticated user', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.feature-flags.index'))
        ->assertOk()
        ->assertViewHas('tenants')
        ->assertViewHas('flagDefinitions');
});
test('index renders flags for tenant', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.tenants.feature-flags.index', $this->tenant))
        ->assertOk()
        ->assertViewHas('flags')
        ->assertViewHas('flagDefinitions');
});
test('update activates selected flags', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->put(route('landlord.tenants.feature-flags.update', $this->tenant), [
            'flags' => [FeatureFlag::LocalAuth->value],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});
test('update with no flags deactivates all', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->put(route('landlord.tenants.feature-flags.update', $this->tenant), [
            'flags' => [],
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});
test('bulk update enables flag for all tenants', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.feature-flags.bulk-update'), [
            'flag' => FeatureFlag::LocalAuth->value,
            'action' => 'enable',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});
test('bulk update disables flag for all tenants', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.feature-flags.bulk-update'), [
            'flag' => FeatureFlag::LocalAuth->value,
            'action' => 'disable',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');
});
