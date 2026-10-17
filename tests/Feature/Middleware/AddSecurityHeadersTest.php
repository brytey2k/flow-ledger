<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use Illuminate\Support\Facades\Route;

test('responses carry the baseline security headers', function () {
    $response = $this->get(route('login'));

    $response->assertHeader('X-Content-Type-Options', 'nosniff');
    $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
    $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    $response->assertHeader('Permissions-Policy');
    expect($response->headers->get('Content-Security-Policy'))->toContain("frame-ancestors 'self'");
});
test('hsts is only sent over https', function () {
    $this->get(route('login'))->assertHeaderMissing('Strict-Transport-Security');

    $this->get(str_replace('http://', 'https://', route('login')))
        ->assertHeader('Strict-Transport-Security');
});
test('a header already set by the controller is not overridden', function () {
    $attachmentCsp = "sandbox; default-src 'none'";

    Route::get('/__test-headers', fn() => response('ok')->header('Content-Security-Policy', $attachmentCsp))
        ->middleware('web');

    $this->get('/__test-headers')->assertHeader('Content-Security-Policy', $attachmentCsp);
});
