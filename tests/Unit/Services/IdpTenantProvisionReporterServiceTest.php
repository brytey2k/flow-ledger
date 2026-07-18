<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\IdpTenantProvisionReporterService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class IdpTenantProvisionReporterServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();

        config([
            'sso.idp_internal_url' => 'https://idp.test',
            'sso.product_slug' => 'flow-ledger',
            'sso.m2m_client_id' => 'test-client-id',
            'sso.m2m_client_secret' => 'test-client-secret',
            'sso.verify_ssl' => true,
        ]);
    }

    #[Test]
    public function report_sends_bearer_token_and_payload(): void
    {
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
    }

    #[Test]
    public function report_throws_when_report_request_fails(): void
    {
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
    }

    #[Test]
    public function report_throws_when_token_request_fails(): void
    {
        Http::fake([
            'https://idp.test/oauth/token' => Http::response('Unauthorized', 401),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Failed to obtain M2M provision-report token from IdP');

        $this->app->make(IdpTenantProvisionReporterService::class)
            ->report('iam-tenant-42', 'env-key-abc', 'already_exists');
    }

    #[Test]
    public function report_reuses_cached_token_on_subsequent_calls(): void
    {
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
    }
}
