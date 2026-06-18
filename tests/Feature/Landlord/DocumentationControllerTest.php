<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use Tests\LandlordTestCase;

class DocumentationControllerTest extends LandlordTestCase
{
    public function test_guest_is_redirected_from_documentation(): void
    {
        $this->get(route('landlord.documentation'))->assertRedirect();
    }

    public function test_authenticated_landlord_can_view_documentation(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.documentation'))
            ->assertOk()
            ->assertViewIs('landlord.documentation.index');
    }
}
