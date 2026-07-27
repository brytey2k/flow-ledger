<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use App\Models\Landlord\User as LandlordUser;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Tenant\User as TenantUser;
use App\Services\LandlordTenantAccessControlService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\Test;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Tests\TestCase;

class TenantRolesAndPermissionsControllerTest extends TestCase
{
    use DatabaseTransactions;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);

        $this->tenant = $this->createTenantWithoutLifecycleEvents();
    }

    private function makeRole(int $id, string $name, Collection|null $permissions = null, int $usersCount = 0, int $permissionsCount = 0): Role
    {
        $role = (new Role())->forceFill([
            'id' => $id,
            'name' => $name,
            'guard_name' => 'web',
            'users_count' => $usersCount,
            'permissions_count' => $permissionsCount,
        ]);
        $role->setRelation('permissions', $permissions ?? new Collection());

        return $role;
    }

    private function makePermission(int $id, string $name): Permission
    {
        return (new Permission())->forceFill([
            'id' => $id,
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    private function makeTenantUser(int $id, Collection|null $roles = null, Collection|null $permissions = null): TenantUser
    {
        $user = (new TenantUser())->forceFill([
            'id' => $id,
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => 'jane-' . $id . '@example.com',
        ]);
        $user->setRelation('roles', $roles ?? new Collection());
        $user->setRelation('permissions', $permissions ?? new Collection());

        return $user;
    }

    #[Test]
    public function guest_cannot_view_roles_index(): void
    {
        $response = $this->get(route('landlord.tenants.roles.index', $this->tenant));

        $response->assertRedirect();
    }

    #[Test]
    public function guest_cannot_view_users_permissions_index(): void
    {
        $response = $this->get(route('landlord.tenants.users-permissions.index', $this->tenant));

        $response->assertRedirect();
    }

    #[Test]
    public function landlord_can_view_roles_index(): void
    {
        $this->actingAsLandlord();

        $roles = new Collection([$this->makeRole(1, 'admin', usersCount: 2, permissionsCount: 5)]);

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock) use ($roles): void {
            $mock->shouldReceive('listRoles')
                ->once()
                ->with(Mockery::on(fn($arg) => $arg->id === $this->tenant->id))
                ->andReturn($roles);
        });

        $response = $this->get(route('landlord.tenants.roles.index', $this->tenant));

        $response->assertOk();
        $response->assertViewIs('landlord.tenants.roles.index');
        $response->assertViewHas('roles', $roles);
    }

    #[Test]
    public function landlord_can_view_role_permissions_edit(): void
    {
        $this->actingAsLandlord();

        $permission = $this->makePermission(10, 'access settings');
        $role = $this->makeRole(1, 'admin', permissions: new Collection([$permission]));
        $permissions = new Collection([$permission]);

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock) use ($role, $permissions): void {
            $mock->shouldReceive('findRole')->once()->andReturn($role);
            $mock->shouldReceive('listPermissions')->once()->andReturn($permissions);
        });

        $response = $this->get(route('landlord.tenants.roles.permissions.edit', [$this->tenant, 1]));

        $response->assertOk();
        $response->assertViewIs('landlord.tenants.roles.permissions');
        $response->assertViewHas('role', $role);
        $response->assertViewHas('permissions', $permissions);
    }

    #[Test]
    public function landlord_can_update_role_permissions(): void
    {
        $landlordUser = $this->actingAsLandlord();

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock) use ($landlordUser): void {
            $mock->shouldReceive('idsNotBelongingToTenantPermissions')->andReturn([]);
            $mock->shouldReceive('syncRolePermissions')
                ->once()
                ->with(
                    Mockery::on(fn($arg) => $arg->id === $this->tenant->id),
                    1,
                    [10, 20],
                    Mockery::on(fn($arg) => $arg->id === $landlordUser->id),
                );
        });

        $response = $this->put(route('landlord.tenants.roles.permissions.update', [$this->tenant, 1]), [
            'permissions' => [10, 20],
        ]);

        $response->assertRedirect(route('landlord.tenants.roles.permissions.edit', [$this->tenant, 1]));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function update_role_permissions_rejects_ids_not_belonging_to_the_tenant(): void
    {
        $this->actingAsLandlord();

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('idsNotBelongingToTenantPermissions')->once()->andReturn([999999]);
            $mock->shouldNotReceive('syncRolePermissions');
        });

        $response = $this->put(route('landlord.tenants.roles.permissions.update', [$this->tenant, 1]), [
            'permissions' => [999999],
        ]);

        $response->assertSessionHasErrors(['permissions']);
    }

    #[Test]
    public function landlord_can_view_users_permissions_index(): void
    {
        $this->actingAsLandlord();

        $users = new LengthAwarePaginator([$this->makeTenantUser(1)], 1, 15);

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock) use ($users): void {
            $mock->shouldReceive('listUsersPaginated')->once()->andReturn($users);
        });

        $response = $this->get(route('landlord.tenants.users-permissions.index', $this->tenant));

        $response->assertOk();
        $response->assertViewIs('landlord.tenants.users.index');
        $response->assertViewHas('users', $users);
    }

    #[Test]
    public function landlord_can_view_user_roles_edit(): void
    {
        $this->actingAsLandlord();

        $role = $this->makeRole(1, 'admin');
        $user = $this->makeTenantUser(5, roles: new Collection([$role]));
        $roles = new Collection([$role]);

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock) use ($user, $roles): void {
            $mock->shouldReceive('findUser')->once()->andReturn($user);
            $mock->shouldReceive('listRoles')->once()->andReturn($roles);
        });

        $response = $this->get(route('landlord.tenants.users.roles.edit', [$this->tenant, 5]));

        $response->assertOk();
        $response->assertViewIs('landlord.tenants.users.roles');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('roles', $roles);
    }

    #[Test]
    public function landlord_can_update_user_roles(): void
    {
        $landlordUser = $this->actingAsLandlord();

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock) use ($landlordUser): void {
            $mock->shouldReceive('idsNotBelongingToTenantRoles')->andReturn([]);
            $mock->shouldReceive('syncUserRoles')
                ->once()
                ->with(
                    Mockery::on(fn($arg) => $arg->id === $this->tenant->id),
                    5,
                    [1],
                    Mockery::on(fn($arg) => $arg->id === $landlordUser->id),
                );
        });

        $response = $this->put(route('landlord.tenants.users.roles.update', [$this->tenant, 5]), [
            'roles' => [1],
        ]);

        $response->assertRedirect(route('landlord.tenants.users.roles.edit', [$this->tenant, 5]));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function update_user_roles_rejects_ids_not_belonging_to_the_tenant(): void
    {
        $this->actingAsLandlord();

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('idsNotBelongingToTenantRoles')->once()->andReturn([999999]);
            $mock->shouldNotReceive('syncUserRoles');
        });

        $response = $this->put(route('landlord.tenants.users.roles.update', [$this->tenant, 5]), [
            'roles' => [999999],
        ]);

        $response->assertSessionHasErrors(['roles']);
    }

    #[Test]
    public function landlord_can_view_user_permissions_edit(): void
    {
        $this->actingAsLandlord();

        $permission = $this->makePermission(10, 'access settings');
        $user = $this->makeTenantUser(5, permissions: new Collection([$permission]));
        $permissions = new Collection([$permission]);

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock) use ($user, $permissions): void {
            $mock->shouldReceive('findUser')->once()->andReturn($user);
            $mock->shouldReceive('listPermissions')->once()->andReturn($permissions);
        });

        $response = $this->get(route('landlord.tenants.users.permissions.edit', [$this->tenant, 5]));

        $response->assertOk();
        $response->assertViewIs('landlord.tenants.users.permissions');
        $response->assertViewHas('user', $user);
        $response->assertViewHas('permissions', $permissions);
    }

    #[Test]
    public function landlord_can_update_user_permissions(): void
    {
        $landlordUser = $this->actingAsLandlord();

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock) use ($landlordUser): void {
            $mock->shouldReceive('idsNotBelongingToTenantPermissions')->andReturn([]);
            $mock->shouldReceive('syncUserPermissions')
                ->once()
                ->with(
                    Mockery::on(fn($arg) => $arg->id === $this->tenant->id),
                    5,
                    [10],
                    Mockery::on(fn($arg) => $arg->id === $landlordUser->id),
                );
        });

        $response = $this->put(route('landlord.tenants.users.permissions.update', [$this->tenant, 5]), [
            'permissions' => [10],
        ]);

        $response->assertRedirect(route('landlord.tenants.users.permissions.edit', [$this->tenant, 5]));
        $response->assertSessionHas('success');
    }

    #[Test]
    public function update_user_permissions_rejects_ids_not_belonging_to_the_tenant(): void
    {
        $this->actingAsLandlord();

        $this->mock(LandlordTenantAccessControlService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('idsNotBelongingToTenantPermissions')->once()->andReturn([999999]);
            $mock->shouldNotReceive('syncUserPermissions');
        });

        $response = $this->put(route('landlord.tenants.users.permissions.update', [$this->tenant, 5]), [
            'permissions' => [999999],
        ]);

        $response->assertSessionHasErrors(['permissions']);
    }

    private function actingAsLandlord(): LandlordUser
    {
        $landlordUser = LandlordUser::query()->create([
            'name' => 'Landlord Admin',
            'email' => 'admin-' . uniqid() . '@landlord.test',
            'password' => Hash::make('password123'),
        ]);

        /** @var Authenticatable $authUser */
        $authUser = $landlordUser;
        $this->actingAs($authUser, 'landlord');

        return $landlordUser;
    }

    private function createTenantWithoutLifecycleEvents(): Tenant
    {
        return Tenant::withoutEvents(static fn(): Tenant => Tenant::create([
            'id' => 'roles-perms-tenant-' . uniqid(),
            'name' => 'Demo Tenant',
            'admin_username' => 'admin',
            'admin_password' => Hash::make('password123'),
            'sender_id' => 'DEMO',
            'reporting_currency' => 'GHS',
            'is_suspended' => false,
        ]));
    }
}
