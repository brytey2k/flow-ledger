<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use Illuminate\Support\Facades\Cache;

test('authenticated user without force logout key can access protected routes', function () {
    Cache::forget("force_logout:{$this->user->id}");

    $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
});
test('authenticated user with force logout key is logged out and redirected', function () {
    Cache::put("force_logout:{$this->user->id}", true, now()->addHour());

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertRedirect(route('login'));
    $this->assertGuest();
});
test('force logout key does not affect guest requests', function () {
    Cache::put("force_logout:{$this->user->id}", true, now()->addHour());

    $this->get(route('login'))->assertOk();
});
test('user can access dashboard after force logout key expires', function () {
    Cache::forget("force_logout:{$this->user->id}");

    $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
});
