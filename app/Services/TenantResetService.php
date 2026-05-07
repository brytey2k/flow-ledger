<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Support\Facades\Artisan;
use Spatie\Permission\PermissionRegistrar;

class TenantResetService
{
    public function __construct(private readonly NewTenantSetupService $newTenantSetupService) {}

    public function reset(Tenant $tenant): void
    {
        tenancy()->initialize($tenant);

        try {
            Artisan::call('migrate:fresh', [
                '--database' => 'tenant',
                '--path' => [database_path('migrations/tenant')],
                '--realpath' => true,
                '--force' => true,
            ]);

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            $this->newTenantSetupService->handleReset($tenant);
        } finally {
            tenancy()->end();
        }
    }
}
