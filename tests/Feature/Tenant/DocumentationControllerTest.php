<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use Tests\TenantAppTestCase;

class DocumentationControllerTest extends TenantAppTestCase
{
    public function test_guest_cannot_access_documentation(): void
    {
        $this->get(route('documentation'))->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_documentation(): void
    {
        $this->actingAs($this->user)
            ->get(route('documentation'))
            ->assertOk()
            ->assertViewIs('tenant.documentation.index');
    }
}
