<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PaymentRequestType;
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use App\Models\Tenant\Department;
use App\Models\Tenant\Level;
use App\Models\Tenant\Position;
use App\Models\Tenant\Setting;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\NewTenantSetupService;
use App\Services\SettingsService;
use Illuminate\Support\Facades\Event;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

function clearTenantState(): void
{
    Setting::query()->delete();
    CashBalanceThreshold::query()->delete();
    Cashbook::query()->delete();
    CurrencyDenomination::query()->delete();
    Currency::query()->delete();
    CostCode::query()->delete();
    Department::query()->delete();
    Position::query()->delete();
    User::query()->delete();
    WorkflowTemplate::query()->delete();
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
test('handle creates a published tenant-wide workflow template for each request type', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

    foreach (PaymentRequestType::cases() as $type) {
        $template = WorkflowTemplate::where('type', $type->value)->first();

        expect($template)->not->toBeNull("WorkflowTemplate for type [{$type->value}] was not created.");
        expect($template->branch_id)->toBeNull();
        expect($template->is_current)->toBeTrue();
        expect($template->status)->toBe('published');
    }

    expect(WorkflowTemplate::count())->toBe(count(PaymentRequestType::cases()));
});
test('handle creates a four stage approval chain for each workflow template', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

    $expectedRoleOrder = [
        'Finance Officer',
        'Finance Manager',
        'Finance Director',
        'Disbursement Officer',
    ];

    foreach (PaymentRequestType::cases() as $type) {
        $template = WorkflowTemplate::where('type', $type->value)->firstOrFail();
        $stages = $template->stages()->with('roles')->get();

        expect($stages)->toHaveCount(4);
        expect($stages->pluck('display_order')->all())->toBe([1, 2, 3, 4]);
        expect($stages->pluck('name')->all())->toBe($expectedRoleOrder);

        foreach ($stages as $index => $stage) {
            expect($stage->roles)->toHaveCount(1);
            expect($stage->roles->first()->name)->toBe($expectedRoleOrder[$index]);
        }
    }
});
test('handle creates default positions', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

    expect(Position::count())->toBe(8);
    $this->assertDatabaseHas('positions', ['name' => 'Finance Officer'], 'tenant');
    $this->assertDatabaseHas('positions', ['name' => 'Executive Director'], 'tenant');
});

test('handle creates default departments with a cost code each', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

    expect(Department::count())->toBe(5);
    expect(CostCode::count())->toBe(5);

    $department = Department::where('name', 'Finance & Accounts')->firstOrFail();
    $this->assertDatabaseHas('cost_codes', [
        'code' => 'FIN-001',
        'department_id' => $department->id,
    ], 'tenant');
});

test('handle creates the GHS currency with denominations', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

    $currency = Currency::where('short_name', 'GHS')->first();
    expect($currency)->not->toBeNull();
    expect($currency->name)->toBe('Ghanaian Cedi');
    expect($currency->denominations()->count())->toBe(14);
});

test('handle creates a zero balance cashbook for the head office branch in GHS', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

    $branch = Branch::where('name', 'Head Office')->firstOrFail();
    $currency = Currency::where('short_name', 'GHS')->firstOrFail();

    $this->assertDatabaseHas('cashbooks', [
        'branch_id' => $branch->id,
        'currency_id' => $currency->id,
        'balance' => '0.00',
    ], 'tenant');
});

test('handle creates a cash balance threshold notifying the admin user', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

    $branch = Branch::where('name', 'Head Office')->firstOrFail();
    $adminUser = User::where('email', 'admin@newco.test')->firstOrFail();

    $threshold = CashBalanceThreshold::where('branch_id', $branch->id)->first();
    expect($threshold)->not->toBeNull();
    expect($threshold->threshold_amount)->toBe('5000.00');
    expect($threshold->notification_user_ids)->toBe([$adminUser->id]);
    expect($threshold->is_active)->toBeTrue();
});

test('handle seeds default settings for the new tenant', function () {
    clearTenantState();

    app(NewTenantSetupService::class)->handle($this->tenant, 'admin@newco.test', 'secret123');

    $branch = Branch::where('name', 'Head Office')->firstOrFail();
    $costCode = CostCode::where('code', 'FIN-001')->firstOrFail();
    $adminRole = Role::where('name', 'admin')->where('guard_name', 'web')->firstOrFail();

    $settingsService = app(SettingsService::class);

    expect($settingsService->getDefaultAdvanceCostCodeId())->toBe($costCode->id);
    expect($settingsService->isExpenseSourceDocumentRequired())->toBeTrue();
    expect($settingsService->isRetirementSourceDocumentRequired())->toBeTrue();
    expect($settingsService->getSsoDefaultBranchId())->toBe($branch->id);

    $reminderSettings = $settingsService->getRetirementReminderSettings();
    expect($reminderSettings['grace_period_days'])->toBe(7);
    expect($reminderSettings['frequency_days'])->toBe(7);
    expect($reminderSettings['notify_submitter'])->toBeTrue();
    expect($reminderSettings['notify_role_ids'])->toBe([$adminRole->id]);
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
