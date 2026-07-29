<?php

declare(strict_types=1);
use App\Services\IdpTenantService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();

    config([
        'sso.idp_internal_url' => 'https://idp.test',
        'sso.m2m_client_id' => 'test-client-id',
        'sso.m2m_client_secret' => 'test-client-secret',
        'sso.verify_ssl' => true,
    ]);
});
test('list tenants fetches token and returns tenants', function () {
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

    expect($tenants)->toHaveCount(2);
    expect($tenants[0]['name'])->toBe('Tenant One');
    expect($tenants[1]['slug'])->toBe('tenant-two');
});
test('list tenants returns cached result on second call', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'https://idp.test/api/m2m/tenants' => Http::sequence()
            ->push(['data' => [['id' => 1, 'name' => 'Cached', 'slug' => 'cached']]])
            ->push(['data' => [['id' => 2, 'name' => 'Second', 'slug' => 'second']]]),
    ]);

    $service = $this->app->make(IdpTenantService::class);
    $first = $service->listTenants();
    $second = $service->listTenants();

    expect($second)->toBe($first);
    expect($first[0]['name'])->toBe('Cached');
});
test('list tenants throws when m2m credentials not configured', function () {
    config(['sso.m2m_client_id' => '', 'sso.m2m_client_secret' => '']);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('SSO M2M client credentials are not configured.');

    $this->app->make(IdpTenantService::class)->listTenants();
});
test('list tenants throws when token request fails', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response('Unauthorized', 401),
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('IDP rejected M2M token request');

    $this->app->make(IdpTenantService::class)->listTenants();
});
test('list tenants throws when tenants request fails', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response(['access_token' => 'token', 'expires_in' => 3600]),
        'https://idp.test/api/m2m/tenants' => Http::response('Server Error', 500),
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Failed to fetch tenants from IDP');

    $this->app->make(IdpTenantService::class)->listTenants();
});
test('list tenants throws on connection exception', function () {
    Http::fake(static function (): never {
        throw new ConnectionException('Could not connect');
    });

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Could not connect to IDP to fetch M2M token');

    $this->app->make(IdpTenantService::class)->listTenants();
});
