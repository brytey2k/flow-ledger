<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Tenant;
use App\Services\NewTenantSetupService;
use App\Services\TenantResetService;
use Illuminate\Support\Facades\Artisan;
use Tests\TenantAppTestCase;

class TenantResetServiceTest extends TenantAppTestCase
{
    public function test_reset_runs_fresh_migration_and_calls_setup(): void
    {
        Artisan::spy();

        $setupService = $this->mock(NewTenantSetupService::class);
        $setupService->shouldReceive('handleReset')
            ->once()
            ->with(\Mockery::type(Tenant::class));

        $service = new TenantResetService($setupService);
        $service->reset($this->tenant);

        Artisan::shouldHaveReceived('call')
            ->once()
            ->with('migrate:fresh', \Mockery::on(fn($args) => ($args['--database'] ?? '') === 'tenant'));

        // Re-initialize tenancy so tearDown can roll back tenant transaction
        tenancy()->initialize($this->tenant);
    }

    public function test_reset_ends_tenancy_even_when_migration_fails(): void
    {
        Artisan::shouldReceive('call')->andThrow(new \RuntimeException('Migration failed'));

        $setupService = $this->mock(NewTenantSetupService::class);
        $setupService->shouldNotReceive('handleReset');

        $service = new TenantResetService($setupService);

        try {
            $service->reset($this->tenant);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Migration failed', $e->getMessage());
        }

        $this->assertNull(tenancy()->tenant);

        tenancy()->initialize($this->tenant);
    }

    public function test_reset_ends_tenancy_even_when_setup_fails(): void
    {
        Artisan::spy();

        $setupService = $this->mock(NewTenantSetupService::class);
        $setupService->shouldReceive('handleReset')
            ->once()
            ->andThrow(new \RuntimeException('Setup failed'));

        $service = new TenantResetService($setupService);

        try {
            $service->reset($this->tenant);
            $this->fail('Expected RuntimeException was not thrown');
        } catch (\RuntimeException $e) {
            $this->assertSame('Setup failed', $e->getMessage());
        }

        $this->assertNull(tenancy()->tenant);

        tenancy()->initialize($this->tenant);
    }
}
