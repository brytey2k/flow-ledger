<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Tenant;
use App\Repositories\TenantRepository;
use App\Services\NewTenantSetupService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

/**
 * Creates a tenant requested by the IAM IdP. Creation is idempotent: if a
 * tenant already exists for the IdP tenant id or the subdomain, no tenant is
 * created and the outcome is reported back to the IdP as `already_exists`.
 */
class CreateTenantFromIdpJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public function __construct(
        public readonly string $idpTenantId,
        public readonly string $name,
        public readonly string $subdomain,
        public readonly string $environmentKey,
    ) {}

    /** @return list<int> */
    public function backoff(): array
    {
        return [30, 60, 120];
    }

    public function handle(TenantRepository $tenantRepository, NewTenantSetupService $newTenantSetupService): void
    {
        if ($tenantRepository->findByIdpTenantId($this->idpTenantId) !== null || Tenant::find($this->subdomain) !== null) {
            ReportTenantProvisionedToIdpJob::dispatch($this->idpTenantId, $this->environmentKey, 'already_exists');

            return;
        }

        $centralDomain = parse_url(config()->string('app.url'), PHP_URL_HOST);

        $adminEmail = 'admin@' . $this->subdomain . '.' . (is_string($centralDomain) ? $centralDomain : '');
        $adminPassword = Str::random(32);

        $newTenantSetupService->createTenant($this->subdomain, $this->name, $adminEmail, $adminPassword, $this->idpTenantId);

        ReportTenantProvisionedToIdpJob::dispatch($this->idpTenantId, $this->environmentKey, 'created');
    }
}
