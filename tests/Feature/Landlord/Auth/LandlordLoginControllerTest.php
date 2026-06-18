<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord\Auth;

use App\Models\Landlord\User;
use Illuminate\Support\Facades\Hash;
use Tests\LandlordTestCase;

class LandlordLoginControllerTest extends LandlordTestCase
{
    // ── Show login form ───────────────────────────────────────────────────────

    public function test_login_form_is_accessible_to_guests(): void
    {
        $this->get(route('landlord.login'))->assertOk();
    }

    public function test_login_form_renders_correct_view(): void
    {
        $this->get(route('landlord.login'))->assertViewIs('landlord.auth.login');
    }

    // ── Login ─────────────────────────────────────────────────────────────────

    public function test_landlord_can_login_with_valid_credentials(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('Password1!'),
        ]);

        $this->post(route('landlord.do-login'), [
            'email' => $user->email,
            'password' => 'Password1!',
        ])->assertRedirect(route('landlord.tenants.index'));

        $this->assertAuthenticatedAs($user, 'landlord');
    }

    public function test_login_fails_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CorrectPassword1!'),
        ]);

        $this->post(route('landlord.do-login'), [
            'email' => $user->email,
            'password' => 'WrongPassword!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('landlord');
    }

    public function test_login_fails_with_nonexistent_email(): void
    {
        $this->post(route('landlord.do-login'), [
            'email' => 'nobody@example.com',
            'password' => 'Password1!',
        ])->assertSessionHasErrors('email');

        $this->assertGuest('landlord');
    }

    public function test_login_requires_email(): void
    {
        $this->post(route('landlord.do-login'), [
            'password' => 'Password1!',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_requires_valid_email_format(): void
    {
        $this->post(route('landlord.do-login'), [
            'email' => 'not-an-email',
            'password' => 'Password1!',
        ])->assertSessionHasErrors('email');
    }

    public function test_login_requires_password(): void
    {
        $user = User::factory()->create();

        $this->post(route('landlord.do-login'), [
            'email' => $user->email,
        ])->assertSessionHasErrors('password');
    }

    // ── Logout ────────────────────────────────────────────────────────────────

    public function test_authenticated_landlord_can_logout(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.logout'));

        $this->assertGuest('landlord');
    }

    public function test_logout_redirects_to_landlord_login(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.logout'))
            ->assertRedirect(route('landlord.login'));
    }
}
