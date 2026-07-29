<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use Tests\LandlordTestCase;

class LocaleControllerTest extends LandlordTestCase
{
    public function test_locale_update_stores_locale_in_session(): void
    {
        $response = $this->post(route('landlord.locale.update'), [
            'locale' => 'fr',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'fr');
    }

    public function test_locale_update_falls_back_to_default_for_unknown_locale(): void
    {
        $response = $this->post(route('landlord.locale.update'), [
            'locale' => 'xx',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', config('app.locale', 'en'));
    }
}
