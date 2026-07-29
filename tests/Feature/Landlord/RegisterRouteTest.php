<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);

test('register route renders placeholder', function () {
    $this->get(route('landlord.register'))
        ->assertOk()
        ->assertSee('Register page - to be implemented', false);
});
