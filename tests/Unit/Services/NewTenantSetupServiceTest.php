<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Level;
use App\Models\Tenant\User;
use App\Services\NewTenantSetupService;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function clearTenantState(): void
{
    User::query()->delete();
    Role::query()->delete();
    Permission::query()->delete();
    Branch::query()->delete();
    Level::query()->delete();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
}
/** @param list<string> $expectedPermissionNames */
function assertRoleHasExactPermissions(string $roleName, array $expectedPermissionNames): void
{
    $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();
    expect($role)->not->toBeNull("Role [{$roleName}] was not created.");

    $actualNames = $role->permissions()->pluck('name')->sort()->values()->all();
    expect($actualNames)->toBe(collect($expectedPermissionNames)->sort()->values()->all());
}
test('handle reset creates all permission keys', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handleReset($this->tenant);

    foreach (PermissionKey::cases() as $key) {
        $this->assertDatabaseHas('permissions', [
            'name' => $key->value,
            'guard_name' => 'web',
        ], 'tenant');
    }
});
test('handle reset creates admin role with all permissions', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handleReset($this->tenant);

    $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->first();
    expect($adminRole)->not->toBeNull();
    expect($adminRole->permissions()->count())->toBe(count(PermissionKey::cases()));
});
test('handle reset creates head office branch', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handleReset($this->tenant);

    $this->assertDatabaseHas('branches', ['name' => 'Head Office'], 'tenant');
});
test('handle reset creates admin user with generated email', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handleReset($this->tenant);

    $centralDomain = parse_url(config('app.url'), PHP_URL_HOST);
    $expectedEmail = 'admin@' . $this->tenant->getTenantKey() . '.' . $centralDomain;

    $this->assertDatabaseHas('users', ['email' => $expectedEmail], 'tenant');
});
test('handle reset assigns admin role to admin user', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handleReset($this->tenant);

    $centralDomain = parse_url(config('app.url'), PHP_URL_HOST);
    $adminEmail = 'admin@' . $this->tenant->getTenantKey() . '.' . $centralDomain;

    $adminUser = User::where('email', $adminEmail)->first();
    expect($adminUser)->not->toBeNull();
    expect($adminUser->hasRole('admin'))->toBeTrue();
});
test('handle reset creates finance officer role with expected permissions', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handleReset($this->tenant);

    assertRoleHasExactPermissions('Finance Officer', [
        'access payment requests',
        'access retirement requests',
        'approve requests',
        'create payment request',
        'create retirement request',
    ]);
});
test('handle reset creates finance manager role with expected permissions', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handleReset($this->tenant);

    assertRoleHasExactPermissions('Finance Manager', [
        'access payment requests',
        'access retirement requests',
        'approve requests',
        'create payment request',
        'create retirement request',
    ]);
});
test('handle reset creates finance director role with expected permissions', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handleReset($this->tenant);

    assertRoleHasExactPermissions('Finance Director', [
        'access payment requests',
        'access retirement requests',
        'approve requests',
        'create payment request',
        'create retirement request',
    ]);
});
test('handle reset creates disbursement officer role with expected permissions', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handleReset($this->tenant);

    assertRoleHasExactPermissions('Disbursement Officer', [
        'access payment requests',
        'access retirement requests',
        'approve requests',
        'create payment request',
        'disburse requests',
        'settle retirements',
    ]);
});
test('handle creates default roles with expected permissions', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

    $financePermissions = [
        'access payment requests',
        'access retirement requests',
        'approve requests',
        'create payment request',
        'create retirement request',
    ];

    assertRoleHasExactPermissions('Finance Officer', $financePermissions);
    assertRoleHasExactPermissions('Finance Manager', $financePermissions);
    assertRoleHasExactPermissions('Finance Director', $financePermissions);
    assertRoleHasExactPermissions('Disbursement Officer', [
        'access payment requests',
        'access retirement requests',
        'approve requests',
        'create payment request',
        'disburse requests',
        'settle retirements',
    ]);
});
test('create tenant creates tenant model with correct fields', function () {
    Event::fake();

    $service = Mockery::mock(NewTenantSetupService::class)->makePartial();
    $service->shouldReceive('handle')->once()->andReturn(null);

    $tenant = $service->createTenant('test-co', 'Test Company', 'admin@test.com', 'secret123');

    expect($tenant)->toBeInstanceOf(Tenant::class);
    expect($tenant->id)->toBe('test-co');
    expect($tenant->name)->toBe('Test Company');
    $this->assertDatabaseHas('tenants', ['id' => 'test-co'], 'pgsql');
});
test('create tenant creates subdomain domain', function () {
    Event::fake();

    $service = Mockery::mock(NewTenantSetupService::class)->makePartial();
    $service->shouldReceive('handle')->once()->andReturn(null);

    $service->createTenant('mycompany', 'My Company', 'admin@mycompany.com', 'secret');

    $expectedDomain = 'mycompany.' . parse_url(config()->string('app.url'), PHP_URL_HOST);
    $this->assertDatabaseHas('domains', ['domain' => $expectedDomain], 'pgsql');
});
