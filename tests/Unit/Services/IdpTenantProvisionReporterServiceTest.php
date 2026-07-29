<?php

declare(strict_types=1);
use App\Services\IdpTenantProvisionReporterService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    Cache::flush();

    config([
        'sso.idp_internal_url' => 'https://idp.test',
        'sso.product_slug' => 'flow-ledger',
        'sso.m2m_client_id' => 'test-client-id',
        'sso.m2m_client_secret' => 'test-client-secret',
        'sso.verify_ssl' => true,
    ]);
});
test('report sends bearer token and payload', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response([
            'access_token' => 'test-m2m-token',
            'expires_in' => 3600,
        ]),
        'https://idp.test/api/m2m/tenants/provisioned' => Http::response(['status' => 'ok']),
    ]);

    $this->app->make(IdpTenantProvisionReporterService::class)
        ->report('iam-tenant-42', 'env-key-abc', 'created');

    Http::assertSent(static fn(ClientRequest $request): bool => $request->url() === 'https://idp.test/oauth/token'
        && $request['grant_type'] === 'client_credentials'
        && $request['client_id'] === 'test-client-id'
        && $request['client_secret'] === 'test-client-secret'
        && $request['scope'] === 'tenant:provision-report');

    Http::assertSent(static fn(ClientRequest $request): bool => $request->url() === 'https://idp.test/api/m2m/tenants/provisioned'
        && $request->hasHeader('Authorization', 'Bearer test-m2m-token')
        && $request['product_slug'] === 'flow-ledger'
        && $request['idp_tenant_id'] === 'iam-tenant-42'
        && $request['environment_key'] === 'env-key-abc'
        && $request['status'] === 'created');
});
test('report throws when report request fails', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response([
            'access_token' => 'test-m2m-token',
            'expires_in' => 3600,
        ]),
        'https://idp.test/api/m2m/tenants/provisioned' => Http::response('Server Error', 500),
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Failed to report tenant provisioning to IdP');

    $this->app->make(IdpTenantProvisionReporterService::class)
        ->report('iam-tenant-42', 'env-key-abc', 'created');
});
test('report throws when token request fails', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response('Unauthorized', 401),
    ]);

    $this->expectException(RuntimeException::class);
    $this->expectExceptionMessage('Failed to obtain M2M provision-report token from IdP');

    $this->app->make(IdpTenantProvisionReporterService::class)
        ->report('iam-tenant-42', 'env-key-abc', 'already_exists');
});
test('report reuses cached token on subsequent calls', function () {
    Http::fake([
        'https://idp.test/oauth/token' => Http::response([
            'access_token' => 'test-m2m-token',
            'expires_in' => 3600,
        ]),
        'https://idp.test/api/m2m/tenants/provisioned' => Http::response(['status' => 'ok']),
    ]);

    $service = $this->app->make(IdpTenantProvisionReporterService::class);
    $service->report('iam-tenant-42', 'env-key-abc', 'created');
    $service->report('iam-tenant-43', 'env-key-def', 'already_exists');

    Http::assertSentCount(3);
});
