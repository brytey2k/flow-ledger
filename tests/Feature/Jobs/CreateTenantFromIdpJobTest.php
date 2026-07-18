<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\CreateTenantFromIdpJob;
use App\Jobs\ReportTenantProvisionedToIdpJob;
use App\Models\Tenant;
use App\Repositories\TenantRepository;
use App\Services\NewTenantSetupService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\LandlordTestCase;

class CreateTenantFromIdpJobTest extends LandlordTestCase
{
    private const IDP_TENANT_ID = 'iam-tenant-42';

    private const SUBDOMAIN = 'acme-idp';

    private const ENVIRONMENT_KEY = 'env-key-abc';

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
    }

    public function test_existing_tenant_by_idp_tenant_id_skips_creation_and_reports_already_exists(): void
    {
        $existing = new Tenant([
            'id' => 'existing-' . Str::random(6),
            'name' => 'Existing Tenant',
            'is_suspended' => false,
            'idp_tenant_id' => self::IDP_TENANT_ID,
        ]);
        $existing->saveQuietly();

        $service = $this->mock(NewTenantSetupService::class);
        $service->shouldNotReceive('createTenant');

        $this->makeJob()->handle($this->app->make(TenantRepository::class), $service);

        Queue::assertPushed(
            ReportTenantProvisionedToIdpJob::class,
            static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->idpTenantId === self::IDP_TENANT_ID
                && $job->environmentKey === self::ENVIRONMENT_KEY
                && $job->status === 'already_exists',
        );
    }

    public function test_existing_tenant_by_subdomain_skips_creation_and_reports_already_exists(): void
    {
        $existing = new Tenant([
            'id' => self::SUBDOMAIN,
            'name' => 'Existing Tenant',
            'is_suspended' => false,
        ]);
        $existing->saveQuietly();

        $service = $this->mock(NewTenantSetupService::class);
        $service->shouldNotReceive('createTenant');

        $this->makeJob()->handle($this->app->make(TenantRepository::class), $service);

        Queue::assertPushed(
            ReportTenantProvisionedToIdpJob::class,
            static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->status === 'already_exists',
        );
    }

    public function test_creates_tenant_and_reports_created(): void
    {
        $expectedEmail = 'admin@' . self::SUBDOMAIN . '.' . parse_url(config()->string('app.url'), PHP_URL_HOST);

        $service = $this->mock(NewTenantSetupService::class);
        $service->shouldReceive('createTenant')
            ->once()
            ->withArgs(
                static fn(string $subdomain, string $name, string $adminEmail, string $adminPassword, string|null $idpTenantId): bool => $subdomain === self::SUBDOMAIN
                    && $name === 'Acme Corp'
                    && $adminEmail === $expectedEmail
                    && strlen($adminPassword) === 32
                    && $idpTenantId === self::IDP_TENANT_ID,
            )
            ->andReturn(new Tenant());

        $this->makeJob()->handle($this->app->make(TenantRepository::class), $service);

        Queue::assertPushed(
            ReportTenantProvisionedToIdpJob::class,
            static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->idpTenantId === self::IDP_TENANT_ID
                && $job->environmentKey === self::ENVIRONMENT_KEY
                && $job->status === 'created',
        );
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeJob(): CreateTenantFromIdpJob
    {
        return new CreateTenantFromIdpJob(
            self::IDP_TENANT_ID,
            'Acme Corp',
            self::SUBDOMAIN,
            self::ENVIRONMENT_KEY,
        );
    }
}
