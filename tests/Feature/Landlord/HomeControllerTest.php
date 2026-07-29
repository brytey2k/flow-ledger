<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use Tests\LandlordTestCase;

class HomeControllerTest extends LandlordTestCase
{
    public function test_guest_can_view_the_marketing_home_page(): void
    {
        $this->get(route('landlord.home'))
            ->assertOk()
            ->assertViewIs('landlord.home')
            ->assertSee('Flow Ledger', false);
    }

    public function test_home_page_links_to_the_landlord_login_route(): void
    {
        $this->get(route('landlord.home'))
            ->assertOk()
            ->assertSee(route('landlord.login'), false);
    }
}
