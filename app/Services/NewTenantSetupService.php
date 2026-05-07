<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class NewTenantSetupService
{
    public function handle(TenantWithDatabase $tenant, string $adminEmail, string $adminPassword): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->createPermissions();

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
        ])->assignRole($adminRole);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function handleReset(TenantWithDatabase $tenant): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->createPermissions();

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        $centralDomain = parse_url(config()->string('app.url'), PHP_URL_HOST);

        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@' . $tenant->getTenantKey() . '.' . $centralDomain,
            'password' => Hash::make('password'),
        ])->assignRole($adminRole);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function createPermissions(): void
    {
        foreach (PermissionKey::cases() as $key) {
            Permission::create(['name' => $key->value, 'guard_name' => 'web']);
        }
    }

    public function createTenant(string $subdomain, string $name, string $adminEmail, string $adminPassword): Tenant
    {
        $tenant = Tenant::create(['id' => $subdomain, 'name' => $name]);
        $tenant->domains()->create([
            'domain' => $subdomain . '.' . parse_url(config()->string('app.url'), PHP_URL_HOST),
        ]);

        $tenant->run(fn() => $this->handle($tenant, $adminEmail, $adminPassword));

        return $tenant;
    }
}
