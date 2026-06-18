<?php

declare(strict_types=1);

namespace Tests\Feature\Middleware;

use Tests\TenantAppTestCase;

class EnsureTenantIsActiveTest extends TenantAppTestCase
{
    public function test_suspended_tenant_returns_403_on_any_route(): void
    {
        $this->tenant->update(['is_suspended' => true]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertForbidden();
    }

    public function test_suspended_tenant_returns_403_on_unauthenticated_routes(): void
    {
        $this->tenant->update(['is_suspended' => true]);

        $response = $this->get(route('login'));

        $response->assertForbidden();
    }

    public function test_active_tenant_allows_access(): void
    {
        $this->tenant->update(['is_suspended' => false]);

        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_tenant_with_no_suspension_flag_allows_access(): void
    {
        // Column defaults to false (NOT NULL); don't set it to verify the default state allows access.
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
    }
}
