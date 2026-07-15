<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\IdpTenantService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class IdpTenantServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'sso.idp_internal_url' => 'https://idp.test',
            'sso.m2m_client_id' => 'test-client-id',
            'sso.m2m_client_secret' => 'test-client-secret',
            'sso.verify_ssl' => true,
        ]);
    }

    #[Test]
    public function list_tenants_fetches_token_and_returns_tenants(): void
    {
        Http::fake([
            'https://idp.test/oauth/token' => Http::response([
                'access_token' => 'test-m2m-token',
                'expires_in' => 3600,
            ]),
            'https://idp.test/api/m2m/tenants' => Http::response([
                'data' => [
                    ['id' => 1, 'name' => 'Tenant One', 'slug' => 'tenant-one'],
                    ['id' => 2, 'name' => 'Tenant Two', 'slug' => 'tenant-two'],
                ],
            ]),
        ]);

        $tenants = $this->app->make(IdpTenantService::class)->listTenants();

        $this->assertCount(2, $tenants);
        $this->assertSame('Tenant One', $tenants[0]['name']);
        $this->assertSame('tenant-two', $tenants[1]['slug']);
    }

    #[Test]
    public function list_tenants_returns_cached_result_on_second_call(): void
    {
        Http::fake([
            'https://idp.test/oauth/token' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            'https://idp.test/api/m2m/tenants' => Http::sequence()
                ->push(['data' => [['id' => 1, 'name' => 'Cached', 'slug' => 'cached']]])
                ->push(['data' => [['id' => 2, 'name' => 'Second', 'slug' => 'second']]]),
        ]);

        $service = $this->app->make(IdpTenantService::class);
        $first = $service->listTenants();
        $second = $service->listTenants();

        $this->assertSame($first, $second);
        $this->assertSame('Cached', $first[0]['name']);
    }

    #[Test]
    public function list_tenants_throws_when_m2m_credentials_not_configured(): void
    {
        config(['sso.m2m_client_id' => '', 'sso.m2m_client_secret' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('SSO M2M client credentials are not configured.');

        $this->app->make(IdpTenantService::class)->listTenants();
    }

    #[Test]
    public function list_tenants_throws_when_token_request_fails(): void
    {
        Http::fake([
            'https://idp.test/oauth/token' => Http::response('Unauthorized', 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('IDP rejected M2M token request');

        $this->app->make(IdpTenantService::class)->listTenants();
    }

    #[Test]
    public function list_tenants_throws_when_tenants_request_fails(): void
    {
        Http::fake([
            'https://idp.test/oauth/token' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
            'https://idp.test/api/m2m/tenants' => Http::response('Server Error', 500),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to fetch tenants from IDP');

        $this->app->make(IdpTenantService::class)->listTenants();
    }

    #[Test]
    public function list_tenants_throws_on_connection_exception(): void
    {
        Http::fake(static function (): never {
            throw new ConnectionException('Could not connect');
        });

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not connect to IDP to fetch M2M token');

        $this->app->make(IdpTenantService::class)->listTenants();
    }
}
