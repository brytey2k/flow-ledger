<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Level;
use App\Models\Tenant\User;
use App\Services\NewTenantSetupService;
use Illuminate\Support\Facades\Event;
use Mockery;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TenantAppTestCase;

class NewTenantSetupServiceTest extends TenantAppTestCase
{
    private function clearTenantState(): void
    {
        User::query()->delete();
        Role::query()->delete();
        Permission::query()->delete();
        Branch::query()->delete();
        Level::query()->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @param list<string> $expectedPermissionNames */
    private function assertRoleHasExactPermissions(string $roleName, array $expectedPermissionNames): void
    {
        $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
        $this->assertNotNull($role, "Role [{$roleName}] was not created.");

        $actualNames = $role->permissions()->pluck('name')->sort()->values()->all();
        $this->assertSame(collect($expectedPermissionNames)->sort()->values()->all(), $actualNames);
    }

    // ── handleReset ───────────────────────────────────────────────────────────

    public function test_handle_reset_creates_all_permission_keys(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handleReset($this->tenant);

        foreach (PermissionKey::cases() as $key) {
            $this->assertDatabaseHas('permissions', [
                'name' => $key->value,
                'guard_name' => 'web',
            ], 'tenant');
        }
    }

    public function test_handle_reset_creates_admin_role_with_all_permissions(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handleReset($this->tenant);

        $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
        $this->assertNotNull($adminRole);
        $this->assertSame(count(PermissionKey::cases()), $adminRole->permissions()->count());
    }

    public function test_handle_reset_creates_head_office_branch(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handleReset($this->tenant);

        $this->assertDatabaseHas('branches', ['name' => 'Head Office'], 'tenant');
    }

    public function test_handle_reset_creates_admin_user_with_generated_email(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handleReset($this->tenant);

        $centralDomain = parse_url(config('app.url'), PHP_URL_HOST);
        $expectedEmail = 'admin@' . $this->tenant->getTenantKey() . '.' . $centralDomain;

        $this->assertDatabaseHas('users', ['email' => $expectedEmail], 'tenant');
    }

    public function test_handle_reset_assigns_admin_role_to_admin_user(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handleReset($this->tenant);

        $centralDomain = parse_url(config('app.url'), PHP_URL_HOST);
        $adminEmail = 'admin@' . $this->tenant->getTenantKey() . '.' . $centralDomain;

        $adminUser = User::where('email', $adminEmail)->first();
        $this->assertNotNull($adminUser);
        $this->assertTrue($adminUser->hasRole('admin'));
    }

    public function test_handle_reset_creates_finance_officer_role_with_expected_permissions(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handleReset($this->tenant);

        $this->assertRoleHasExactPermissions('Finance Officer', [
            'access payment requests',
            'access retirement requests',
            'approve requests',
            'create payment request',
            'create retirement request',
        ]);
    }

    public function test_handle_reset_creates_finance_manager_role_with_expected_permissions(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handleReset($this->tenant);

        $this->assertRoleHasExactPermissions('Finance Manager', [
            'access payment requests',
            'access retirement requests',
            'approve requests',
            'create payment request',
            'create retirement request',
        ]);
    }

    public function test_handle_reset_creates_finance_director_role_with_expected_permissions(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handleReset($this->tenant);

        $this->assertRoleHasExactPermissions('Finance Director', [
            'access payment requests',
            'access retirement requests',
            'approve requests',
            'create payment request',
            'create retirement request',
        ]);
    }

    public function test_handle_reset_creates_disbursement_officer_role_with_expected_permissions(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handleReset($this->tenant);

        $this->assertRoleHasExactPermissions('Disbursement Officer', [
            'access payment requests',
            'access retirement requests',
            'approve requests',
            'create payment request',
            'disburse requests',
            'settle retirements',
        ]);
    }

    // ── handle ────────────────────────────────────────────────────────────────

    public function test_handle_creates_default_roles_with_expected_permissions(): void
    {
        $this->clearTenantState();

        app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

        $financePermissions = [
            'access payment requests',
            'access retirement requests',
            'approve requests',
            'create payment request',
            'create retirement request',
        ];

        $this->assertRoleHasExactPermissions('Finance Officer', $financePermissions);
        $this->assertRoleHasExactPermissions('Finance Manager', $financePermissions);
        $this->assertRoleHasExactPermissions('Finance Director', $financePermissions);
        $this->assertRoleHasExactPermissions('Disbursement Officer', [
            'access payment requests',
            'access retirement requests',
            'approve requests',
            'create payment request',
            'disburse requests',
            'settle retirements',
        ]);
    }

    // ── createTenant ──────────────────────────────────────────────────────────

    public function test_create_tenant_creates_tenant_model_with_correct_fields(): void
    {
        Event::fake();

        $service = Mockery::mock(NewTenantSetupService::class)->makePartial();
        $service->shouldReceive('handle')->once()->andReturn(null);

        $tenant = $service->createTenant('test-co', 'Test Company', 'admin@test.com', 'secret123');

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertSame('test-co', $tenant->id);
        $this->assertSame('Test Company', $tenant->name);
        $this->assertDatabaseHas('tenants', ['id' => 'test-co'], 'pgsql');
    }

    public function test_create_tenant_creates_subdomain_domain(): void
    {
        Event::fake();

        $service = Mockery::mock(NewTenantSetupService::class)->makePartial();
        $service->shouldReceive('handle')->once()->andReturn(null);

        $service->createTenant('mycompany', 'My Company', 'admin@mycompany.com', 'secret');

        $expectedDomain = 'mycompany.' . parse_url(config()->string('app.url'), PHP_URL_HOST);
        $this->assertDatabaseHas('domains', ['domain' => $expectedDomain], 'pgsql');
    }
}
