<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
test('locale is set from session', function () {
    $response = $this->actingAs($this->user)
        ->withSession(['locale' => 'fr'])
        ->get(route('dashboard'));

    $response->assertOk();
    expect(app()->getLocale())->toBe('fr');
});
test('locale defaults to app locale when session and user locale absent', function () {
    $this->user->update(['locale' => null]);

    $response = $this->actingAs($this->user)
        ->withSession([])
        ->get(route('dashboard'));

    $response->assertOk();
    expect(app()->getLocale())->toBe(config('app.locale', 'en'));
});
test('session locale takes precedence over user locale', function () {
    $this->user->update(['locale' => 'en']);

    $response = $this->actingAs($this->user)
        ->withSession(['locale' => 'fr'])
        ->get(route('dashboard'));

    $response->assertOk();
    expect(app()->getLocale())->toBe('fr');
});
test('user locale is used when no session locale', function () {
    $this->user->update(['locale' => 'en']);

    $response = $this->actingAs($this->user)
        ->withSession([])
        ->get(route('dashboard'));

    $response->assertOk();
    expect(app()->getLocale())->toBe('en');
});
