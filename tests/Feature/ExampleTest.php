<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
test('tenant dashboard requires authentication', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});
test('authenticated user can access dashboard', function () {
    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertOk();
});
