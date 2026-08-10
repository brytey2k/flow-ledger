<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Role;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;

test('sync permissions creates missing permissions', function () {
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
});
test('sync permissions succeeds when all permissions exist', function () {
    $this->artisan('permissions:sync')
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);
});
test('sync permissions with prune removes orphaned permissions', function () {
    Permission::create(['name' => 'orphaned.permission.xyz', 'guard_name' => 'web']);

    $this->artisan('permissions:sync --prune')
        ->assertSuccessful();

    // The command calls tenancy()->end() — re-initialize so tearDown can roll back
    tenancy()->initialize($this->tenant);

    $this->assertDatabaseMissing('permissions', [
        'name' => 'orphaned.permission.xyz',
        'guard_name' => 'web',
    ], 'tenant');
});
test('sync permissions with prune does nothing when no orphans', function () {
    $this->artisan('permissions:sync --prune')
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);
});

test('sync permissions grants newly created permissions to the system admin role', function () {
    $permissionKey = PermissionKey::cases()[0];

    Permission::where('guard_name', 'web')->where('name', $permissionKey->value)->delete();

    $this->artisan('permissions:sync')
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    $systemAdminRole = Role::findByName(config()->string('roles.system_admin_name'), 'web');

    expect($systemAdminRole->hasPermissionTo($permissionKey->value))->toBeTrue();
});

test('sync permissions does not restore a permission previously revoked from the system admin role', function () {
    $revokedKey = PermissionKey::cases()[0];
    $recreatedKey = PermissionKey::cases()[1];

    $systemAdminRole = Role::findByName(config()->string('roles.system_admin_name'), 'web');
    $systemAdminRole->revokePermissionTo($revokedKey->value);

    Permission::where('guard_name', 'web')->where('name', $recreatedKey->value)->delete();

    // permissions:sync calls tenancy()->end(), which purges the tenant DB
    // connection — anything still uncommitted on it would be rolled back by
    // Postgres when the connection closes. Commit this setup as its own unit
    // of work so it survives that purge.
    DB::connection('tenant')->commit();

    $this->artisan('permissions:sync')
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    $systemAdminRole = Role::findByName(config()->string('roles.system_admin_name'), 'web');

    expect($systemAdminRole->hasPermissionTo($recreatedKey->value))->toBeTrue()
        ->and($systemAdminRole->hasPermissionTo($revokedKey->value))->toBeFalse();

    // Undo the committed revoke, then re-open a transaction so tearDown's
    // rollback still has one to unwind — otherwise this leaks into later
    // tests sharing the same ephemeral tenant database.
    $systemAdminRole->givePermissionTo($revokedKey->value);
    DB::connection('tenant')->beginTransaction();
});

test('sync permissions does not fail when no system admin role exists', function () {
    $systemAdminRole = Role::findByName(config()->string('roles.system_admin_name'), 'web');
    $originalPermissions = $systemAdminRole->permissions->pluck('name')->all();
    $systemAdminRole->delete();

    $permissionKey = PermissionKey::cases()[0];
    Permission::where('guard_name', 'web')->where('name', $permissionKey->value)->delete();

    // permissions:sync calls tenancy()->end(), which purges the tenant DB
    // connection — the uncommitted role deletion above would otherwise be
    // rolled back by Postgres when the connection closes, defeating the
    // point of this test.
    DB::connection('tenant')->commit();

    $this->artisan('permissions:sync')
        ->assertSuccessful();

    tenancy()->initialize($this->tenant);

    // Recreate the role, then re-open a transaction so tearDown's rollback
    // still has one to unwind — otherwise this leaks into later tests
    // sharing the same ephemeral tenant database.
    $restoredRole = Role::create(['name' => config()->string('roles.system_admin_name'), 'guard_name' => 'web']);
    $restoredRole->givePermissionTo($originalPermissions);
    DB::connection('tenant')->beginTransaction();
});
