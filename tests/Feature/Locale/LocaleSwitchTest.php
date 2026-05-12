<?php

declare(strict_types=1);

namespace Tests\Feature\Locale;

use Tests\TenantAppTestCase;

class LocaleSwitchTest extends TenantAppTestCase
{
    public function test_locale_update_stores_locale_in_session(): void
    {
        $response = $this->actingAs($this->user)->post(route('locale.update'), [
            'locale' => 'en',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'en');
    }

    public function test_locale_update_falls_back_to_default_for_unknown_locale(): void
    {
        $response = $this->actingAs($this->user)->post(route('locale.update'), [
            'locale' => 'fr',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('locale', 'fr');
    }
}
