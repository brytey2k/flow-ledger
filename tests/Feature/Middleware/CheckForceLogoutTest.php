<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Illuminate\Support\Facades\Cache;
use Tests\TenantAppTestCase;

class CheckForceLogoutTest extends TenantAppTestCase
{
    public function test_authenticated_user_without_force_logout_key_can_access_protected_routes(): void
    {
        Cache::forget("force_logout:{$this->user->id}");

        $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
    }

    public function test_authenticated_user_with_force_logout_key_is_logged_out_and_redirected(): void
    {
        Cache::put("force_logout:{$this->user->id}", true, now()->addHour());

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_force_logout_key_does_not_affect_guest_requests(): void
    {
        Cache::put("force_logout:{$this->user->id}", true, now()->addHour());

        $this->get(route('login'))->assertOk();
    }

    public function test_user_can_access_dashboard_after_force_logout_key_expires(): void
    {
        Cache::forget("force_logout:{$this->user->id}");

        $this->actingAs($this->user)->get(route('dashboard'))->assertOk();
    }
}
