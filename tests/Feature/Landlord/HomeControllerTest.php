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

    public function test_home_page_links_to_the_register_route_and_not_the_landlord_login_route(): void
    {
        $this->get(route('landlord.home'))
            ->assertOk()
            ->assertSee(route('landlord.register'), false)
            ->assertDontSee(route('landlord.login'), false);
    }

    public function test_home_page_renders_translated_content_for_the_active_locale(): void
    {
        $this->withSession(['locale' => 'fr'])
            ->get(route('landlord.home'))
            ->assertOk()
            ->assertSee(__('landing.hero.headline', [], 'fr'), false);
    }

    public function test_home_page_sets_html_lang_and_dir_attributes(): void
    {
        $this->get(route('landlord.home'))
            ->assertOk()
            ->assertSee('<html lang="en" dir="ltr">', false);
    }
}
