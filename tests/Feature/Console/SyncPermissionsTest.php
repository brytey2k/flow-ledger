<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Enums\Tenant\PermissionKey;
use Spatie\Permission\Models\Permission;
use Tests\TenantAppTestCase;

class SyncPermissionsTest extends TenantAppTestCase
{
    public function test_sync_permissions_creates_missing_permissions(): void
    {
        Permission::where('guard_name', 'web')
            ->where('name', PermissionKey::cases()[0]->value)
            ->delete();

        $this->artisan('permissions:sync')
            ->assertSuccessful();

        // The command calls tenancy()->end() — re-initialize so tearDown can roll back
        tenancy()->initialize($this->tenant);

        $this->assertDatabaseHas('permissions', [
            'name' => PermissionKey::cases()[0]->value,
            'guard_name' => 'web',
        ], 'tenant');
    }

    public function test_sync_permissions_succeeds_when_all_permissions_exist(): void
    {
        $this->artisan('permissions:sync')
            ->assertSuccessful();

        tenancy()->initialize($this->tenant);
    }

    public function test_sync_permissions_with_prune_removes_orphaned_permissions(): void
    {
        Permission::create(['name' => 'orphaned.permission.xyz', 'guard_name' => 'web']);

        $this->artisan('permissions:sync --prune')
            ->assertSuccessful();

        // The command calls tenancy()->end() — re-initialize so tearDown can roll back
        tenancy()->initialize($this->tenant);

        $this->assertDatabaseMissing('permissions', [
            'name' => 'orphaned.permission.xyz',
            'guard_name' => 'web',
        ], 'tenant');
    }

    public function test_sync_permissions_with_prune_does_nothing_when_no_orphans(): void
    {
        $this->artisan('permissions:sync --prune')
            ->assertSuccessful();

        tenancy()->initialize($this->tenant);
    }
}
