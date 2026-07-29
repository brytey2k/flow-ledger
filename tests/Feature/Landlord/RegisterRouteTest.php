<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use Tests\LandlordTestCase;

class RegisterRouteTest extends LandlordTestCase
{
    public function test_register_route_renders_placeholder(): void
    {
        $this->get(route('landlord.register'))
            ->assertOk()
            ->assertSee('Register page - to be implemented', false);
    }
}
