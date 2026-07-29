<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Jobs\CreateTenantFromIdpJob;
use App\Jobs\ReportTenantProvisionedToIdpJob;
use App\Models\Tenant;
use App\Repositories\TenantRepository;
use App\Services\NewTenantSetupService;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

const IDP_TENANT_ID_CREATE_TENANT_FROM_IDP_JOB = 'iam-tenant-42';
const SUBDOMAIN_CREATE_TENANT_FROM_IDP_JOB = 'acme-idp';
const ENVIRONMENT_KEY_CREATE_TENANT_FROM_IDP_JOB = 'env-key-abc';
beforeEach(function () {
    Queue::fake();
});
test('existing tenant by idp tenant id skips creation and reports already exists', function () {
    $existing = new Tenant([
        'id' => 'existing-' . Str::random(6),
        'name' => 'Existing Tenant',
        'is_suspended' => false,
        'idp_tenant_id' => IDP_TENANT_ID_CREATE_TENANT_FROM_IDP_JOB,
    ]);
    $existing->saveQuietly();

    $service = $this->mock(NewTenantSetupService::class);
    $service->shouldNotReceive('createTenant');

    makeJob()->handle($this->app->make(TenantRepository::class), $service);

    Queue::assertPushed(
        ReportTenantProvisionedToIdpJob::class,
        static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->idpTenantId === IDP_TENANT_ID_CREATE_TENANT_FROM_IDP_JOB
            && $job->environmentKey === ENVIRONMENT_KEY_CREATE_TENANT_FROM_IDP_JOB
            && $job->status === 'already_exists',
    );
});
test('existing tenant by subdomain skips creation and reports already exists', function () {
    $existing = new Tenant([
        'id' => SUBDOMAIN_CREATE_TENANT_FROM_IDP_JOB,
        'name' => 'Existing Tenant',
        'is_suspended' => false,
    ]);
    $existing->saveQuietly();

    $service = $this->mock(NewTenantSetupService::class);
    $service->shouldNotReceive('createTenant');

    makeJob()->handle($this->app->make(TenantRepository::class), $service);

    Queue::assertPushed(
        ReportTenantProvisionedToIdpJob::class,
        static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->status === 'already_exists',
    );
});
test('creates tenant and reports created', function () {
    $expectedEmail = 'admin@' . SUBDOMAIN_CREATE_TENANT_FROM_IDP_JOB . '.' . parse_url(config()->string('app.url'), PHP_URL_HOST);

    $service = $this->mock(NewTenantSetupService::class);
    $service->shouldReceive('createTenant')
        ->once()
        ->withArgs(
            static fn(string $subdomain, string $name, string $adminEmail, string $adminPassword, string|null $idpTenantId): bool => $subdomain === SUBDOMAIN_CREATE_TENANT_FROM_IDP_JOB
                && $name === 'Acme Corp'
                && $adminEmail === $expectedEmail
                && strlen($adminPassword) === 32
                && $idpTenantId === IDP_TENANT_ID_CREATE_TENANT_FROM_IDP_JOB,
        )
        ->andReturn(new Tenant());

    makeJob()->handle($this->app->make(TenantRepository::class), $service);

    Queue::assertPushed(
        ReportTenantProvisionedToIdpJob::class,
        static fn(ReportTenantProvisionedToIdpJob $job): bool => $job->idpTenantId === IDP_TENANT_ID_CREATE_TENANT_FROM_IDP_JOB
            && $job->environmentKey === ENVIRONMENT_KEY_CREATE_TENANT_FROM_IDP_JOB
            && $job->status === 'created',
    );
});
// ── Helpers ───────────────────────────────────────────────────────────────
function makeJob(): CreateTenantFromIdpJob
{
    return new CreateTenantFromIdpJob(
        IDP_TENANT_ID_CREATE_TENANT_FROM_IDP_JOB,
        'Acme Corp',
        SUBDOMAIN_CREATE_TENANT_FROM_IDP_JOB,
        ENVIRONMENT_KEY_CREATE_TENANT_FROM_IDP_JOB,
    );
}
