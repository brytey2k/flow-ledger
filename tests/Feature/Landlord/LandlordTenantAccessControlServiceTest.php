<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use App\Enums\Tenant\PermissionKey;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Permission;
use App\Models\Role;
use App\Services\LandlordTenantAccessControlService;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantAppTestCase;

class LandlordTenantAccessControlServiceTest extends TenantAppTestCase
{
    private function makeLandlordUser(): LandlordUser
    {
        return LandlordUser::query()->create([
            'name' => 'Test Landlord',
            'email' => 'landlord-' . uniqid() . '@landlord.test',
            'password' => Hash::make('password'),
        ]);
    }

    #[Test]
    public function it_syncs_role_permissions_with_no_actor_permission_check(): void
    {
        $role = Role::create(['name' => 'landlord_test_role_' . uniqid(), 'guard_name' => 'web']);
        $permission = Permission::where('name', PermissionKey::AccessSettings->value)->firstOrFail();
        $landlordUser = $this->makeLandlordUser();

        app(LandlordTenantAccessControlService::class)
            ->syncRolePermissions($this->tenant, $role->id, [$permission->id], $landlordUser);

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo($permission));
    }

    #[Test]
    public function it_records_an_activity_log_entry_with_the_landlord_as_causer(): void
    {
        $role = Role::create(['name' => 'landlord_test_role_' . uniqid(), 'guard_name' => 'web']);
        $permission = Permission::where('name', PermissionKey::AccessSettings->value)->firstOrFail();
        $landlordUser = $this->makeLandlordUser();

        app(LandlordTenantAccessControlService::class)
            ->syncRolePermissions($this->tenant, $role->id, [$permission->id], $landlordUser);

        $activity = \Spatie\Activitylog\Models\Activity::query()
            ->where('subject_id', $role->id)
            ->where('causer_id', $landlordUser->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('landlord_user', $activity->causer_type);
    }

    #[Test]
    public function it_reports_permission_ids_that_do_not_belong_to_the_tenant(): void
    {
        $unknown = app(LandlordTenantAccessControlService::class)
            ->idsNotBelongingToTenantPermissions($this->tenant, [999999999]);

        $this->assertSame([999999999], $unknown);
    }

    #[Test]
    public function it_reports_role_ids_that_do_not_belong_to_the_tenant(): void
    {
        $unknown = app(LandlordTenantAccessControlService::class)
            ->idsNotBelongingToTenantRoles($this->tenant, [999999999]);

        $this->assertSame([999999999], $unknown);
    }

    #[Test]
    public function it_returns_empty_when_all_ids_belong_to_the_tenant(): void
    {
        $permission = Permission::where('name', PermissionKey::AccessSettings->value)->firstOrFail();

        $unknown = app(LandlordTenantAccessControlService::class)
            ->idsNotBelongingToTenantPermissions($this->tenant, [$permission->id]);

        $this->assertSame([], $unknown);
    }
}
