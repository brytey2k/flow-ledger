<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Features\DelegateIdentityToIdp;
use Laravel\Pennant\Feature;

test('user with must change password is redirected to password change', function () {
    $this->user->update(['must_change_password' => true]);

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertRedirect(route('password.change'));
});
test('user without must change password can access protected routes', function () {
    $this->user->update(['must_change_password' => false]);

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertOk();
});
test('user with must change password can still access password change route', function () {
    $this->user->update(['must_change_password' => true]);

    $response = $this->actingAs($this->user)->get(route('password.change'));

    $response->assertOk();
});
test('user with must change password can still post to password change', function () {
    $this->user->update(['must_change_password' => true]);

    // Send a valid password — if the middleware had blocked it we'd be redirected to password.change,
    // but the controller should run and redirect to dashboard instead.
    $response = $this->actingAs($this->user)->put(route('password.change.update'), [
        'password' => 'NewSecurePassword1!',
        'password_confirmation' => 'NewSecurePassword1!',
    ]);

    $response->assertRedirect(route('dashboard'));
});
test('user with must change password can post to logout', function () {
    $this->user->update(['must_change_password' => true]);

    $response = $this->actingAs($this->user)->post(route('logout'));

    // Logout redirects to login — confirming it passed through the middleware.
    $response->assertRedirect();
    $this->assertGuest();
});
test('unauthenticated user is not affected by middleware', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});
test('delegate identity to idp bypasses force password change', function () {
    $this->user->update(['must_change_password' => true]);

    Feature::for($this->tenant)->activate(DelegateIdentityToIdp::class);

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertOk();
});
test('force password change still applies without delegate identity flag', function () {
    $this->user->update(['must_change_password' => true]);

    Feature::for($this->tenant)->deactivate(DelegateIdentityToIdp::class);

    $response = $this->actingAs($this->user)->get(route('dashboard'));

    $response->assertRedirect(route('password.change'));
});
test('password change page shows sign out later option', function () {
    $this->user->update(['must_change_password' => true]);

    $response = $this->actingAs($this->user)->get(route('password.change'));

    $response->assertOk();
    $response->assertSee(route('logout'), false);
});
