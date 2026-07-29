<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
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
