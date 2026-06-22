<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\ImpersonationToken;
use Tests\TenantAppTestCase;

class ImpersonationControllerTest extends TenantAppTestCase
{
    // ── Exit Impersonation ────────────────────────────────────────────────────

    public function test_exit_impersonation_requires_authentication(): void
    {
        $this->post(route('exit-impersonation'))->assertRedirect(route('login'));
    }

    public function test_exit_impersonation_clears_session_and_redirects_to_landlord(): void
    {
        $this->actingAs($this->user)
            ->withSession(['impersonated' => true])
            ->post(route('exit-impersonation'))
            ->assertRedirect(route('landlord.tenants.index'));

        $this->assertGuest();
    }

    public function test_exit_impersonation_removes_impersonated_session_key(): void
    {
        $this->actingAs($this->user)
            ->withSession(['impersonated' => true])
            ->post(route('exit-impersonation'))
            ->assertSessionMissing('impersonated');
    }

    // ── Impersonate ───────────────────────────────────────────────────────────

    public function test_impersonate_route_sets_session_and_logs_in_as_tenant_user(): void
    {
        $token = ImpersonationToken::create([
            'token' => Str::random(128),
            'tenant_id' => $this->tenant->getTenantKey(),
            'user_id' => (string) $this->user->id,
            'auth_guard' => 'web',
            'redirect_url' => '/dashboard',
            'created_at' => now(),
        ]);

        $this->get(route('impersonate', $token->token))
            ->assertRedirect('/dashboard')
            ->assertSessionHas('impersonated', true);

        $this->assertAuthenticatedAs($this->user);
    }
}
