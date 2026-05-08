<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TenantAppTestCase;

class ExampleTest extends TenantAppTestCase
{
    public function test_tenant_dashboard_requires_authentication(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get(route('dashboard'));

        $response->assertOk();
    }
}
