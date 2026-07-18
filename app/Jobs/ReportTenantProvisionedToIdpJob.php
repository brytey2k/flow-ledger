<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\IdpTenantProvisionReporterService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Confirms a tenant-provisioning outcome back to the IAM IdP. The reporter
 * service throws on HTTP failure so this job retries with backoff.
 */
class ReportTenantProvisionedToIdpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $idpTenantId,
        public readonly string $environmentKey,
        public readonly string $status,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(IdpTenantProvisionReporterService $reporterService): void
    {
        $reporterService->report($this->idpTenantId, $this->environmentKey, $this->status);
    }
}
