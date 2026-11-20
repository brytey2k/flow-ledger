<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
test('guest cannot access documentation', function () {
    $this->get(route('documentation'))->assertRedirect(route('login'));
});
test('authenticated user is redirected to the public guide', function () {
    $this->actingAs($this->user)
        ->get(route('documentation'))
        ->assertRedirect(config('app.docs_url'));
});
