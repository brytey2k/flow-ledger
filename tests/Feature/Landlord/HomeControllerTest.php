<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
test('guest can view the marketing home page', function () {
    $this->get(route('landlord.home'))
        ->assertOk()
        ->assertViewIs('landlord.home')
        ->assertSee('Flow Ledger', false);
});
test('home page links to the register route and not the landlord login route', function () {
    $this->get(route('landlord.home'))
        ->assertOk()
        ->assertSee(route('landlord.register'), false)
        ->assertDontSee(route('landlord.login'), false);
});
test('home page renders translated content for the active locale', function () {
    $this->withSession(['locale' => 'fr'])
        ->get(route('landlord.home'))
        ->assertOk()
        ->assertSee(__('landing.hero.headline', [], 'fr'), false);
});
test('home page sets html lang and dir attributes', function () {
    $this->get(route('landlord.home'))
        ->assertOk()
        ->assertSee('<html lang="en" dir="ltr">', false);
});
