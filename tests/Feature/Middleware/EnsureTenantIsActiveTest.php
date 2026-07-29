<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
test('suspended tenant returns 403 on any route', function () {
    $this->tenant->update(['is_suspended' => true]);

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertForbidden();
});
test('suspended tenant returns 403 on unauthenticated routes', function () {
    $this->tenant->update(['is_suspended' => true]);

    $response = $this->get(route('login'));

    $response->assertForbidden();
});
test('active tenant allows access', function () {
    $this->tenant->update(['is_suspended' => false]);

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertOk();
});
test('tenant with no suspension flag allows access', function () {
    // Column defaults to false (NOT NULL); don't set it to verify the default state allows access.
    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertOk();
});
