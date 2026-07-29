<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Jobs\BootstrapTenant;
use App\Services\NewTenantSetupService;

test('job calls handle reset with the tenant', function () {
    $service = $this->mock(NewTenantSetupService::class);
    $service->shouldReceive('handleReset')
        ->once()
        ->with($this->tenant);

    $job = new BootstrapTenant($this->tenant);
    $job->handle($service);

    // Re-initialize tenancy so tearDown can roll back tenant transaction
    tenancy()->initialize($this->tenant);
});
test('job ends tenancy after setup', function () {
    $this->mock(NewTenantSetupService::class)
        ->shouldReceive('handleReset')
        ->once();

    $job = new BootstrapTenant($this->tenant);
    $job->handle(app(NewTenantSetupService::class));

    expect(tenancy()->tenant)->toBeNull();

    tenancy()->initialize($this->tenant);
});
