<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Helpers\ResolvesAutomatedTestTenantMarker;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Tests\Helpers\Models\Domain;
use Tests\Helpers\Models\Tenant;

class RemoveAutomatedTestTenant extends Command
{
    use ResolvesAutomatedTestTenantMarker;

    protected $signature = 'app:remove-automated-tests-tenant {--sweep : Remove every tenant this run\'s parallel workers created, not just one marker}';

    protected $description = 'Remove the tenant(s) used for automated tests';

    public function handle(): void
    {
        Config::set('tenancy.tenant_model', Tenant::class);
        Config::set('tenancy.domain_model', Domain::class);

        if ($this->option('sweep')) {
            $this->sweep();

            return;
        }

        $markerFile = $this->markerFilePath($this->marker());

        // Prefer the tenant this exact invocation created, so a concurrent
        // test run's teardown can't delete a tenant still in use elsewhere.
        // Fall back to the old "most recent" heuristic if no marker was
        // recorded (e.g. app:create-automated-tests-tenant wasn't run first).
        /** @var Tenant|null $tenant */
        $tenant = File::exists($markerFile)
            ? Tenant::find(File::get($markerFile))
            : Tenant::latest()->first();

        if (! $tenant) {
            $this->error('No tenant found.');

            return;
        }

        $this->removeTenant($tenant, $markerFile);
    }

    /**
     * Remove every tenant created by this run's parallel workers.
     *
     * Each worker (keyed by ParaTest's TEST_TOKEN) provisions its own tenant
     * up front and records it under its own marker file
     * (automated-tenant-{AUTOMATED_TEST_TENANT_MARKER}-w{TEST_TOKEN}.id). This
     * command is invoked directly from the parallel test runner script's
     * cleanup trap, outside of any worker, so TEST_TOKEN is not set here —
     * sweep every worker marker for this run instead of a single one.
     */
    private function sweep(): void
    {
        $baseMarker = $this->marker();
        $pattern = $this->markerFilePath($baseMarker . '-w*');

        $markerFiles = File::glob($pattern);

        if (count($markerFiles) === 0) {
            $this->info('No parallel worker tenants found to remove.');

            return;
        }

        foreach ($markerFiles as $markerFile) {
            if (! is_string($markerFile)) {
                continue;
            }

            /** @var Tenant|null $tenant */
            $tenant = Tenant::find(File::get($markerFile));

            if (! $tenant) {
                $this->error("Tenant does not exist for marker file: {$markerFile}");
                File::delete($markerFile);

                continue;
            }

            $this->removeTenant($tenant, $markerFile);
        }
    }

    private function removeTenant(Tenant $tenant, string $markerFile): void
    {
        /** @var \Illuminate\Database\Eloquent\Relations\HasMany<\Stancl\Tenancy\Database\Models\Domain, Tenant> $domainsRelation */
        $domainsRelation = $tenant->domains();
        $domainsRelation->delete();
        $tenantKey = $tenant->getTenantKey();
        $tenantKeyStr = is_scalar($tenantKey) ? (string) $tenantKey : '';
        $this->info('Domains deleted for tenant: ' . $tenantKeyStr);

        $tenant->delete();
        $this->info('Tenant deleted: ' . $tenantKeyStr);

        $dbName = $tenant->database()->getName();

        if (is_string($dbName) && $tenant->database()->manager()->databaseExists($dbName)) {
            $tenant->database()->manager()->deleteDatabase($tenant);
            $this->info('Tenant database deleted: ' . $dbName);
        }

        File::delete($markerFile);
    }
}
