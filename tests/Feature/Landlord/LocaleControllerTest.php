<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
test('locale update stores locale in session', function () {
    $response = $this->post(route('landlord.locale.update'), [
        'locale' => 'fr',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('locale', 'fr');
});
test('locale update falls back to default for unknown locale', function () {
    $response = $this->post(route('landlord.locale.update'), [
        'locale' => 'xx',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('locale', config('app.locale', 'en'));
});
