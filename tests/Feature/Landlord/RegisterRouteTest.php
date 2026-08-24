<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);

test('register route renders the self-registration halted page', function () {
    $this->get(route('landlord.register'))
        ->assertOk()
        ->assertViewIs('landlord.auth.register-halted')
        ->assertSee(__('auth.registration_halted_heading'), false);
});
