<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Tests\TenantAppTestCase;

class ForcePasswordChangeTest extends TenantAppTestCase
{
    public function test_user_with_must_change_password_is_redirected_to_password_change(): void
    {
        $this->user->update(['must_change_password' => true]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertRedirect(route('password.change'));
    }

    public function test_user_without_must_change_password_can_access_protected_routes(): void
    {
        $this->user->update(['must_change_password' => false]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_user_with_must_change_password_can_still_access_password_change_route(): void
    {
        $this->user->update(['must_change_password' => true]);

        $response = $this->actingAs($this->user)->get(route('password.change'));

        $response->assertOk();
    }

    public function test_user_with_must_change_password_can_still_post_to_password_change(): void
    {
        $this->user->update(['must_change_password' => true]);

        // Send a valid password — if the middleware had blocked it we'd be redirected to password.change,
        // but the controller should run and redirect to dashboard instead.
        $response = $this->actingAs($this->user)->put(route('password.change.update'), [
            'password' => 'NewSecurePassword1!',
            'password_confirmation' => 'NewSecurePassword1!',
        ]);

        $response->assertRedirect(route('dashboard'));
    }

    public function test_user_with_must_change_password_can_post_to_logout(): void
    {
        $this->user->update(['must_change_password' => true]);

        $response = $this->actingAs($this->user)->post(route('logout'));

        // Logout redirects to login — confirming it passed through the middleware.
        $response->assertRedirect();
        $this->assertGuest();
    }

    public function test_unauthenticated_user_is_not_affected_by_middleware(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }
}
