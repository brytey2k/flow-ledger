<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
test('guest is redirected from documentation', function () {
    $this->get(route('landlord.documentation'))->assertRedirect();
});
test('authenticated landlord is redirected to the public guide', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.documentation'))
        ->assertRedirect(config('app.docs_url'));
});
