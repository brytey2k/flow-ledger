<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Features\LocalAuth;
use App\Features\VerifyLoginWithIdp;
use App\Models\Tenant\User;
use App\Services\IdpAccessVerificationService;
use Illuminate\Support\Facades\Hash;
use Laravel\Pennant\Feature;
use Tests\TenantAppTestCase;

class LoginControllerTest extends TenantAppTestCase
{
    // ── Show login form ───────────────────────────────────────────────────────

    public function test_login_form_is_accessible_to_guests(): void
    {
        $this->get(route('login'))->assertOk();
    }

    public function test_login_form_renders_correct_view(): void
    {
        $this->get(route('login'))->assertViewIs('tenant.auth.login');
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password1!',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPassword1!'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'WrongPassword!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'nobody@example.com',
            'password' => 'Password1!',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_requires_email(): void
    {
        $response = $this->post(route('login'), [
            'password' => 'Password1!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_valid_email_format(): void
    {
        $response = $this->post(route('login'), [
            'email' => 'not-an-email',
            'password' => 'Password1!',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_login_requires_password(): void
    {
        $user = User::factory()->create();

        $response = $this->post(route('login'), [
            'email' => $user->email,
        ]);

        $response->assertSessionHasErrors('password');
    }

    public function test_login_with_remember_me_sets_cookie(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password1!'),
        ]);

        $response = $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password1!',
            'remember' => true,
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    // ── LocalAuth feature flag ────────────────────────────────────────────────

    public function test_login_form_shows_form_when_local_auth_is_enabled(): void
    {
        Feature::for($this->tenant)->activate(LocalAuth::class);

        $this->get(route('login'))->assertOk()->assertSee('sign_in_form', false);
    }

    public function test_login_form_hides_form_when_local_auth_is_disabled(): void
    {
        Feature::for($this->tenant)->deactivate(LocalAuth::class);

        $this->get(route('login'))->assertOk()->assertDontSee('sign_in_form', false);
    }

    public function test_login_returns_403_when_local_auth_is_disabled(): void
    {
        Feature::for($this->tenant)->deactivate(LocalAuth::class);

        $this->post(route('login'), [
            'email' => 'user@example.com',
            'password' => 'Password1!',
        ])->assertForbidden();
    }

    // ── VerifyLoginWithIdp feature flag ───────────────────────────────────────

    public function test_login_succeeds_when_verify_with_idp_is_enabled_and_idp_grants_access(): void
    {
        Feature::for($this->tenant)->activate(VerifyLoginWithIdp::class);

        $this->mock(IdpAccessVerificationService::class)
            ->shouldReceive('userHasAccess')
            ->once()
            ->andReturn(true);

        $user = User::factory()->create(['password' => Hash::make('Password1!')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password1!',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_fails_when_verify_with_idp_is_enabled_and_idp_denies_access(): void
    {
        Feature::for($this->tenant)->activate(VerifyLoginWithIdp::class);

        $this->mock(IdpAccessVerificationService::class)
            ->shouldReceive('userHasAccess')
            ->once()
            ->andReturn(false);

        $user = User::factory()->create(['password' => Hash::make('Password1!')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password1!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_verify_with_idp_is_not_called_when_feature_is_disabled(): void
    {
        Feature::for($this->tenant)->deactivate(VerifyLoginWithIdp::class);

        $this->mock(IdpAccessVerificationService::class)
            ->shouldNotReceive('userHasAccess');

        $user = User::factory()->create(['password' => Hash::make('Password1!')]);

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'Password1!',
        ])->assertRedirect(route('dashboard'));
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'));

        $this->assertGuest();
    }

    public function test_logout_redirects_to_login(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirect(route('login'));
    }
}
