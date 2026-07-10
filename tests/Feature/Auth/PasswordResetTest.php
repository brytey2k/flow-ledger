<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Features\DelegateIdentityToIdp;
use App\Features\LocalAuth;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Laravel\Pennant\Feature;
use Tests\TenantAppTestCase;

class PasswordResetTest extends TenantAppTestCase
{
    // ── Forgot Password Form ──────────────────────────────────────────────────

    public function test_forgot_password_form_renders(): void
    {
        $response = $this->get(route('password.request'));

        $response->assertOk();
        $response->assertViewIs('tenant.auth.forgot-password');
    }

    // ── Send Reset Link ───────────────────────────────────────────────────────

    public function test_reset_link_sent_for_valid_email(): void
    {
        Notification::fake();

        $response = $this->post(route('password.email'), [
            'email' => $this->user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertSentTo($this->user, ResetPassword::class);
    }

    public function test_reset_link_returns_success_for_unknown_email(): void
    {
        // Avoid email enumeration — always return the same status message
        $response = $this->post(route('password.email'), [
            'email' => 'nobody@example.com',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
    }

    public function test_reset_link_fails_validation_without_email(): void
    {
        $response = $this->post(route('password.email'), []);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_reset_link_returns_403_when_local_auth_disabled(): void
    {
        Feature::for($this->tenant)->deactivate(LocalAuth::class);

        $response = $this->post(route('password.email'), [
            'email' => $this->user->email,
        ]);

        $response->assertForbidden();
    }

    public function test_reset_link_not_sent_when_identity_delegated_to_idp(): void
    {
        Notification::fake();
        Feature::for($this->tenant)->activate(DelegateIdentityToIdp::class);

        $response = $this->post(route('password.email'), [
            'email' => $this->user->email,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('status');
        Notification::assertNothingSent();
    }

    // ── Reset Password Form ───────────────────────────────────────────────────

    public function test_reset_password_form_renders_with_token(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->get(route('password.reset', ['token' => $token]));

        $response->assertOk();
        $response->assertViewIs('tenant.auth.reset-password');
        $response->assertViewHas('token', $token);
    }

    public function test_reset_password_form_redirects_to_login_when_identity_delegated_to_idp(): void
    {
        Feature::for($this->tenant)->activate(DelegateIdentityToIdp::class);
        $token = Password::createToken($this->user);

        $response = $this->get(route('password.reset', ['token' => $token]));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas('status');
    }

    // ── Reset Password ────────────────────────────────────────────────────────

    public function test_password_reset_succeeds_and_logs_in(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $response->assertSessionHas('success');
        $this->assertTrue(Hash::check('newpassword123', $this->user->fresh()->password));
    }

    public function test_password_reset_fails_with_invalid_token(): void
    {
        $response = $this->post(route('password.update'), [
            'token' => 'invalid-token',
            'email' => $this->user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_password_reset_fails_with_mismatched_confirmation(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'different456',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_reset_fails_with_short_password(): void
    {
        $token = Password::createToken($this->user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertSessionHasErrors(['password']);
    }

    public function test_password_reset_returns_403_when_local_auth_disabled(): void
    {
        Feature::for($this->tenant)->deactivate(LocalAuth::class);
        $token = Password::createToken($this->user);

        $response = $this->post(route('password.update'), [
            'token' => $token,
            'email' => $this->user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertForbidden();
    }

    public function test_password_not_reset_when_identity_delegated_to_idp(): void
    {
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
        $this->assertSame($originalPassword, $this->user->fresh()->password);
    }
}
