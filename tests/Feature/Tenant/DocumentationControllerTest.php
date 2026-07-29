<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
test('guest cannot access documentation', function () {
    $this->get(route('documentation'))->assertRedirect(route('login'));
});
test('authenticated user can view documentation', function () {
    $this->actingAs($this->user)
        ->get(route('documentation'))
        ->assertOk()
        ->assertViewIs('tenant.documentation.index');
});
