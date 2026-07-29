<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Pennant\Feature;

test('forgot password form renders', function () {
    $response = $this->get(route('password.request'));

    $response->assertOk();
    $response->assertViewIs('tenant.auth.forgot-password');
});
test('reset link sent for valid email', function () {
    Notification::fake();

    $response = $this->post(route('password.email'), [
        'email' => $this->user->email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');
    Notification::assertSentTo($this->user, ResetPassword::class);
});
test('reset link returns success for unknown email', function () {
    // Avoid email enumeration — always return the same status message
    $response = $this->post(route('password.email'), [
        'email' => 'nobody@example.com',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');
});
test('reset link fails validation without email', function () {
    $response = $this->post(route('password.email'), []);

    $response->assertSessionHasErrors(['email']);
});
test('reset link returns 403 when local auth disabled', function () {
    Feature::for($this->tenant)->deactivate(LocalAuth::class);

    $response = $this->post(route('password.email'), [
        'email' => $this->user->email,
    ]);

    $response->assertForbidden();
});
test('reset link not sent when identity delegated to idp', function () {
    Notification::fake();
    Feature::for($this->tenant)->activate(DelegateIdentityToIdp::class);

    $response = $this->post(route('password.email'), [
        'email' => $this->user->email,
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');
    Notification::assertNothingSent();
});
test('reset password form renders with token', function () {
    $token = Password::createToken($this->user);

    $response = $this->get(route('password.reset', ['token' => $token]));

    $response->assertOk();
    $response->assertViewIs('tenant.auth.reset-password');
    $response->assertViewHas('token', $token);
});
test('reset password form redirects to login when identity delegated to idp', function () {
    Feature::for($this->tenant)->activate(DelegateIdentityToIdp::class);
    $token = Password::createToken($this->user);

    $response = $this->get(route('password.reset', ['token' => $token]));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
});
test('password reset succeeds and logs in', function () {
    $token = Password::createToken($this->user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertRedirect(route('dashboard'));
    $response->assertSessionHas('success');
    expect(Hash::check('newpassword123', $this->user->fresh()->password))->toBeTrue();
});
test('password reset fails with invalid token', function () {
    $response = $this->post(route('password.update'), [
        'token' => 'invalid-token',
        'email' => $this->user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertSessionHasErrors(['email']);
});
test('password reset fails with mismatched confirmation', function () {
    $token = Password::createToken($this->user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'different456',
    ]);

    $response->assertSessionHasErrors(['password']);
});
test('password reset fails with short password', function () {
    $token = Password::createToken($this->user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'short',
        'password_confirmation' => 'short',
    ]);

    $response->assertSessionHasErrors(['password']);
});
test('password reset returns 403 when local auth disabled', function () {
    Feature::for($this->tenant)->deactivate(LocalAuth::class);
    $token = Password::createToken($this->user);

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertForbidden();
});
test('password not reset when identity delegated to idp', function () {
    Feature::for($this->tenant)->activate(DelegateIdentityToIdp::class);
    $token = Password::createToken($this->user);
    $originalPassword = $this->user->password;

    $response = $this->post(route('password.update'), [
        'token' => $token,
        'email' => $this->user->email,
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('status');
    expect($this->user->fresh()->password)->toBe($originalPassword);
});
