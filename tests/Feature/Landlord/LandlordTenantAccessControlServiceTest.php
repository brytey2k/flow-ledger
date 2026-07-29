<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Permission;
use App\Models\Role;
use App\Services\LandlordTenantAccessControlService;
use Illuminate\Support\Facades\Hash;

function makeLandlordUser(): LandlordUser
{
    return LandlordUser::query()->create([
        'name' => 'Test Landlord',
        'email' => 'landlord-' . uniqid() . '@landlord.test',
        'password' => Hash::make('password'),
    ]);
}
it('syncs role permissions with no actor permission check', function () {
    $role = Role::create(['name' => 'landlord_test_role_' . uniqid(), 'guard_name' => 'web']);
    $permission = Permission::where('name', PermissionKey::AccessSettings->value)->firstOrFail();
    $landlordUser = makeLandlordUser();

    app(LandlordTenantAccessControlService::class)
        ->syncRolePermissions($this->tenant, $role->id, [$permission->id], $landlordUser);

    $role->refresh();
    expect($role->hasPermissionTo($permission))->toBeTrue();
});
it('records an activity log entry with the landlord as causer', function () {
    $role = Role::create(['name' => 'landlord_test_role_' . uniqid(), 'guard_name' => 'web']);
    $permission = Permission::where('name', PermissionKey::AccessSettings->value)->firstOrFail();
    $landlordUser = makeLandlordUser();

    app(LandlordTenantAccessControlService::class)
        ->syncRolePermissions($this->tenant, $role->id, [$permission->id], $landlordUser);

    $activity = Spatie\Activitylog\Models\Activity::query()
        ->where('subject_id', $role->id)
        ->where('causer_id', $landlordUser->id)
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->causer_type)->toBe('landlord_user');
});
it('reports permission ids that do not belong to the tenant', function () {
    $unknown = app(LandlordTenantAccessControlService::class)
        ->idsNotBelongingToTenantPermissions($this->tenant, [999999999]);

    expect($unknown)->toBe([999999999]);
});
it('reports role ids that do not belong to the tenant', function () {
    $unknown = app(LandlordTenantAccessControlService::class)
        ->idsNotBelongingToTenantRoles($this->tenant, [999999999]);

    expect($unknown)->toBe([999999999]);
});
it('returns empty when all ids belong to the tenant', function () {
    $permission = Permission::where('name', PermissionKey::AccessSettings->value)->firstOrFail();

    $unknown = app(LandlordTenantAccessControlService::class)
        ->idsNotBelongingToTenantPermissions($this->tenant, [$permission->id]);

    expect($unknown)->toBe([]);
});
