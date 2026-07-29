<?php

declare(strict_types=1);
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;

test('x forwarded host is not trusted', function () {
    $request = Request::create('http://victim-tenant.flow-ledger.test/login', 'GET');
    $request->headers->set('X-Forwarded-Host', 'evil.com');
    $request->headers->set('X-Forwarded-Proto', 'https');
    $request->server->set('REMOTE_ADDR', '203.0.113.1');

    $middleware = $this->app->make(TrustProxies::class);

    $middleware->handle($request, function (Request $request): void {
        expect($request->getHost())->toBe('victim-tenant.flow-ledger.test');
    });
});
test('x forwarded proto is still trusted', function () {
    $request = Request::create('http://victim-tenant.flow-ledger.test/login', 'GET');
    $request->headers->set('X-Forwarded-Proto', 'https');
    $request->server->set('REMOTE_ADDR', '203.0.113.1');

    $middleware = $this->app->make(TrustProxies::class);

    $middleware->handle($request, function (Request $request): void {
        expect($request->isSecure())->toBeTrue();
    });
});
