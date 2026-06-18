<?php

declare(strict_types=1);

namespace Tests\Feature\Jobs;

use App\Jobs\BootstrapTenant;
use App\Services\NewTenantSetupService;
use Tests\TenantAppTestCase;

class BootstrapTenantTest extends TenantAppTestCase
{
    public function test_job_calls_handle_reset_with_the_tenant(): void
    {
        $service = $this->mock(NewTenantSetupService::class);
        $service->shouldReceive('handleReset')
            ->once()
            ->with($this->tenant);

        $job = new BootstrapTenant($this->tenant);
        $job->handle($service);

        // Re-initialize tenancy so tearDown can roll back tenant transaction
        tenancy()->initialize($this->tenant);
    }

    public function test_job_ends_tenancy_after_setup(): void
    {
        $this->mock(NewTenantSetupService::class)
            ->shouldReceive('handleReset')
            ->once();

        $job = new BootstrapTenant($this->tenant);
        $job->handle(app(NewTenantSetupService::class));

        $this->assertNull(tenancy()->tenant);

        tenancy()->initialize($this->tenant);
    }
}
