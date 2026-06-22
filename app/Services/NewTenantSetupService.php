<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\BranchClosure;
use App\Models\Tenant\Level;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
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

        $branch = $this->createDefaultBranch();

        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
            'branch_id' => $branch->id,
            'operational_branch_id' => $branch->id,
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

        $branch = $this->createDefaultBranch();

        User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@' . (is_scalar($tenant->getTenantKey()) ? (string) $tenant->getTenantKey() : '') . '.' . $centralDomain,
            'password' => Hash::make(Str::random(32)),
            'branch_id' => $branch->id,
            'operational_branch_id' => $branch->id,
        ])->assignRole($adminRole);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function createDefaultBranch(): Branch
    {
        $level = Level::firstOrCreate(
            ['name' => 'Default'],
            ['position' => 1],
        );

        $branch = Branch::firstOrCreate(
            ['name' => 'Head Office', 'level_id' => $level->id],
            ['position' => 1],
        );

        BranchClosure::firstOrCreate([
            'ancestor_id' => $branch->id,
            'descendant_id' => $branch->id,
            'depth' => 0,
        ]);

        return $branch;
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
