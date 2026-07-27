<?php

declare(strict_types=1);

namespace Tests\Unit\Bootstrap;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrustProxiesHostHeaderTest extends TestCase
{
    public function test_x_forwarded_host_is_not_trusted(): void
    {
        $request = Request::create('http://victim-tenant.flow-ledger.test/login', 'GET');
        $request->headers->set('X-Forwarded-Host', 'evil.com');
        $request->headers->set('X-Forwarded-Proto', 'https');
        $request->server->set('REMOTE_ADDR', '203.0.113.1');

        $middleware = $this->app->make(TrustProxies::class);

        $middleware->handle($request, function (Request $request): void {
            $this->assertSame('victim-tenant.flow-ledger.test', $request->getHost());
        });
    }

    public function test_x_forwarded_proto_is_still_trusted(): void
    {
        $request = Request::create('http://victim-tenant.flow-ledger.test/login', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'https');
        $request->server->set('REMOTE_ADDR', '203.0.113.1');

        $middleware = $this->app->make(TrustProxies::class);

        $middleware->handle($request, function (Request $request): void {
            $this->assertTrue($request->isSecure());
        });
    }
}
