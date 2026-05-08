<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Tenant\PermissionKey;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\Helpers\Models\Domain;
use Tests\Helpers\Models\Tenant;

class CreateAutomatedTestTenant extends Command
{
    protected $signature = 'app:create-automated-tests-tenant';

    protected $description = 'Create a tenant to be used for automated tests';

    public function handle(): void
    {
        Config::set('tenancy.tenant_model', Tenant::class);
        Config::set('tenancy.domain_model', Domain::class);

        $id = 'test' . time();
        $centralDomain = parse_url(config()->string('app.url'), PHP_URL_HOST);

        /** @var Tenant $tenant */
        $tenant = Tenant::create(['id' => $id, 'name' => 'Automated Test Tenant']);
        /** @var \Illuminate\Database\Eloquent\Relations\HasMany<\Stancl\Tenancy\Database\Models\Domain, Tenant> $domainsRelation */
        $domainsRelation = $tenant->domains();
        $domainsRelation->create(['domain' => $id . '.' . $centralDomain]);

        $tenant->run(function () {
            app(PermissionRegistrar::class)->forgetCachedPermissions();

            foreach (PermissionKey::cases() as $key) {
                Permission::create(['name' => $key->value, 'guard_name' => 'web']);
            }

            $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
            $adminRole->givePermissionTo(Permission::all());

            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });

        $tenantKey = $tenant->getTenantKey();
        $this->info('Tenant ID: ' . (is_scalar($tenantKey) ? (string) $tenantKey : ''));
        $this->info('Tenant database: ' . $tenant->database()->getName());
    }
}
