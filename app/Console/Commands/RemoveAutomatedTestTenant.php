<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Tests\Helpers\Models\Domain;
use Tests\Helpers\Models\Tenant;

class RemoveAutomatedTestTenant extends Command
{
    protected $signature = 'app:remove-automated-tests-tenant';

    protected $description = 'Remove the tenant used for automated tests';

    public function handle(): void
    {
        Config::set('tenancy.tenant_model', Tenant::class);
        Config::set('tenancy.domain_model', Domain::class);

        /** @var Tenant|null $tenant */
        $tenant = Tenant::latest()->first();

        if (! $tenant) {
            $this->error('No tenant found.');

            return;
        }

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
    }
}
