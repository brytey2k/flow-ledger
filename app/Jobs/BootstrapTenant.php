<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\NewTenantSetupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class BootstrapTenant implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly TenantWithDatabase $tenant) {}

    public function handle(NewTenantSetupService $service): void
    {
        tenancy()->initialize($this->tenant);
        $service->handle($this->tenant);
        tenancy()->end();
    }
}
