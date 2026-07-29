<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant;
use App\Services\NewTenantSetupService;
use App\Services\TenantResetService;
use Illuminate\Support\Facades\Artisan;

test('reset runs fresh migration and calls setup', function () {
    Artisan::spy();

    $setupService = $this->mock(NewTenantSetupService::class);
    $setupService->shouldReceive('handleReset')
        ->once()
        ->with(Mockery::type(Tenant::class));

    $service = new TenantResetService($setupService);
    $service->reset($this->tenant);

    Artisan::shouldHaveReceived('call')
        ->once()
        ->with('migrate:fresh', Mockery::on(fn($args) => ($args['--database'] ?? '') === 'tenant'));

    // Re-initialize tenancy so tearDown can roll back tenant transaction
    tenancy()->initialize($this->tenant);
});
test('reset ends tenancy even when migration fails', function () {
    Artisan::shouldReceive('call')->andThrow(new RuntimeException('Migration failed'));

    $setupService = $this->mock(NewTenantSetupService::class);
    $setupService->shouldNotReceive('handleReset');

    $service = new TenantResetService($setupService);

    try {
        $service->reset($this->tenant);
        $this->fail('Expected RuntimeException was not thrown');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Migration failed');
    }

    expect(tenancy()->tenant)->toBeNull();

    tenancy()->initialize($this->tenant);
});
test('reset ends tenancy even when setup fails', function () {
    Artisan::spy();

    $setupService = $this->mock(NewTenantSetupService::class);
    $setupService->shouldReceive('handleReset')
        ->once()
        ->andThrow(new RuntimeException('Setup failed'));

    $service = new TenantResetService($setupService);

    try {
        $service->reset($this->tenant);
        $this->fail('Expected RuntimeException was not thrown');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('Setup failed');
    }

    expect(tenancy()->tenant)->toBeNull();

    tenancy()->initialize($this->tenant);
});
