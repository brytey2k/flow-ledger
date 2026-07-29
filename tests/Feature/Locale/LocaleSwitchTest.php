<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
test('locale update stores locale in session', function () {
    $response = $this->actingAs($this->user)->post(route('locale.update'), [
        'locale' => 'en',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('locale', 'en');
});
test('locale update falls back to default for unknown locale', function () {
    $response = $this->actingAs($this->user)->post(route('locale.update'), [
        'locale' => 'fr',
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('locale', 'fr');
});
