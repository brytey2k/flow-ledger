<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant;

test('primary_domain_hostname returns the domain marked as primary, not just the first one', function () {
    // The tenant already has one primary domain from test setup; add a second,
    // non-primary domain that would previously have been picked by
    // domains()->first() depending on insertion order.
    $this->tenant->domains()->create(['domain' => 'legacy.' . $this->tenant->id . '.test', 'is_primary' => false]);
    $newPrimary = $this->tenant->domains()->create(['domain' => 'primary.' . $this->tenant->id . '.test', 'is_primary' => false]);

    $this->tenant->domains()->update(['is_primary' => false]);
    $newPrimary->update(['is_primary' => true]);

    expect($this->tenant->fresh()->primary_domain_hostname)->toBe($newPrimary->domain);
});

test('primary_domain_hostname falls back to the oldest domain when none is marked primary', function () {
    $this->tenant->domains()->update(['is_primary' => false]);
    $oldest = $this->tenant->domains()->oldest('id')->first();
    $this->tenant->domains()->create(['domain' => 'newer.' . $this->tenant->id . '.test', 'is_primary' => false]);

    expect($this->tenant->fresh()->primary_domain_hostname)->toBe($oldest->domain);
});

test('primary_domain_hostname throws when the tenant has no domain', function () {
    // Deliberately unsaved: persisting a tenant fires TenantCreated, which
    // provisions a tenant database — not something a plain CREATE DATABASE
    // can do inside this test's transaction.
    $tenant = new Tenant(['id' => 'domainless-tenant']);

    expect(fn() => $tenant->primary_domain_hostname)->toThrow(RuntimeException::class);
});
