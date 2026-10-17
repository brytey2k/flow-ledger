<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Services\PlatformOverviewService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RefreshTenantHealthJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public int $timeout = 120;

    public function __construct(public string $tenantId) {}

    public function handle(PlatformOverviewService $overview): void
    {
        $tenant = Tenant::find($this->tenantId);

        if ($tenant instanceof Tenant) {
            $overview->refresh($tenant);
        }
    }
}
