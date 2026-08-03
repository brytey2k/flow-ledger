<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tenant\CurrencyDenominationType;
use App\Enums\Tenant\PaymentRequestType;
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\BranchClosure;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Department;
use App\Models\Tenant\Level;
use App\Models\Tenant\Position;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Contracts\TenantWithDatabase;

class NewTenantSetupService
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /** @var array<string, list<PermissionKey>> */
    private const DEFAULT_ROLE_PERMISSIONS = [
        'Finance Officer' => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::ApproveRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::CreateRetirementRequest,
        ],
        'Finance Manager' => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::ApproveRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::CreateRetirementRequest,
        ],
        'Finance Director' => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::ApproveRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::CreateRetirementRequest,
        ],
        'Disbursement Officer' => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::ApproveRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::DisburseRequests,
            PermissionKey::SettleRetirements,
        ],
    ];

    /** @var list<string> */
    private const DEFAULT_WORKFLOW_STAGE_ROLES = [
        'Finance Officer',
        'Finance Manager',
        'Finance Director',
        'Disbursement Officer',
    ];

    /** @var list<string> */
    private const DEFAULT_POSITIONS = [
        'Executive Director',
        'Finance Director',
        'Finance Manager',
        'Finance Officer',
        'Operations Manager',
        'Procurement Officer',
        'Administrative Officer',
        'Office Assistant',
    ];

    /** @var array<string, array{code: string, name: string}> */
    private const DEFAULT_DEPARTMENTS = [
        'Finance & Accounts' => ['code' => 'FIN-001', 'name' => 'General Finance & Accounts'],
        'Administration' => ['code' => 'ADM-001', 'name' => 'General Administration'],
        'Operations' => ['code' => 'OPS-001', 'name' => 'General Operations'],
        'Human Resources' => ['code' => 'HR-001', 'name' => 'General Human Resources'],
        'Procurement' => ['code' => 'PRC-001', 'name' => 'General Procurement'],
    ];

    /** @var list<array{value: string, label: string, type: CurrencyDenominationType}> */
    private const GHS_DENOMINATIONS = [
        ['value' => '200.00', 'label' => 'GHS 200 Note', 'type' => CurrencyDenominationType::Note],
        ['value' => '100.00', 'label' => 'GHS 100 Note', 'type' => CurrencyDenominationType::Note],
        ['value' => '50.00', 'label' => 'GHS 50 Note', 'type' => CurrencyDenominationType::Note],
        ['value' => '20.00', 'label' => 'GHS 20 Note', 'type' => CurrencyDenominationType::Note],
        ['value' => '10.00', 'label' => 'GHS 10 Note', 'type' => CurrencyDenominationType::Note],
        ['value' => '5.00', 'label' => 'GHS 5 Note', 'type' => CurrencyDenominationType::Note],
        ['value' => '2.00', 'label' => 'GHS 2 Note', 'type' => CurrencyDenominationType::Note],
        ['value' => '1.00', 'label' => 'GHS 1 Note', 'type' => CurrencyDenominationType::Note],
        ['value' => '1.00', 'label' => 'GHS 1 Coin', 'type' => CurrencyDenominationType::Coin],
        ['value' => '0.50', 'label' => '50 Pesewas Coin', 'type' => CurrencyDenominationType::Coin],
        ['value' => '0.20', 'label' => '20 Pesewas Coin', 'type' => CurrencyDenominationType::Coin],
        ['value' => '0.10', 'label' => '10 Pesewas Coin', 'type' => CurrencyDenominationType::Coin],
        ['value' => '0.05', 'label' => '5 Pesewas Coin', 'type' => CurrencyDenominationType::Coin],
        ['value' => '0.01', 'label' => '1 Pesewa Coin', 'type' => CurrencyDenominationType::Coin],
    ];

    private const DEFAULT_CASH_BALANCE_THRESHOLD_AMOUNT = '5000.00';

    private const DEFAULT_ADVANCE_COST_CODE = 'FIN-001';

    private const RETIREMENT_REMINDER_GRACE_PERIOD_DAYS = 7;

    private const RETIREMENT_REMINDER_FREQUENCY_DAYS = 7;

    public function handle(TenantWithDatabase $tenant, string $adminEmail, string $adminPassword): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->createPermissions();

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        assert($adminRole instanceof Role);
        $adminRole->givePermissionTo(Permission::all());

        $this->createDefaultRoles();

        $branch = $this->createDefaultBranch();

        $this->createDefaultWorkflowTemplates();

        $this->createDefaultPositions();
        $this->createDefaultDepartmentsAndCostCodes();
        $currency = $this->createDefaultCurrency();
        $this->createDefaultCashbook($branch, $currency);

        $adminUser = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => $adminEmail,
            'password' => Hash::make($adminPassword),
            'branch_id' => $branch->id,
            'operational_branch_id' => $branch->id,
        ]);
        $adminUser->assignRole($adminRole);

        $this->createDefaultCashBalanceThreshold($branch, $adminUser);
        $this->createDefaultSettings($branch, $adminRole);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function handleReset(TenantWithDatabase $tenant): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->createPermissions();

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        assert($adminRole instanceof Role);
        $adminRole->givePermissionTo(Permission::all());

        $this->createDefaultRoles();

        $centralDomain = parse_url(config()->string('app.url'), PHP_URL_HOST);

        $branch = $this->createDefaultBranch();

        $this->createDefaultWorkflowTemplates();

        $this->createDefaultPositions();
        $this->createDefaultDepartmentsAndCostCodes();
        $currency = $this->createDefaultCurrency();
        $this->createDefaultCashbook($branch, $currency);

        $adminUser = User::create([
            'first_name' => 'Admin',
            'last_name' => 'User',
            'email' => 'admin@' . (is_scalar($tenant->getTenantKey()) ? (string) $tenant->getTenantKey() : '') . '.' . $centralDomain,
            'password' => Hash::make(Str::random(32)),
            'branch_id' => $branch->id,
            'operational_branch_id' => $branch->id,
        ]);
        $adminUser->assignRole($adminRole);

        $this->createDefaultCashBalanceThreshold($branch, $adminUser);
        $this->createDefaultSettings($branch, $adminRole);

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

    protected function createDefaultPositions(): void
    {
        foreach (self::DEFAULT_POSITIONS as $name) {
            Position::firstOrCreate(['name' => $name]);
        }
    }

    protected function createDefaultDepartmentsAndCostCodes(): void
    {
        foreach (self::DEFAULT_DEPARTMENTS as $departmentName => $costCode) {
            $department = Department::firstOrCreate(['name' => $departmentName]);

            CostCode::firstOrCreate(
                ['code' => $costCode['code']],
                ['name' => $costCode['name'], 'department_id' => $department->id],
            );
        }
    }

    protected function createDefaultCurrency(): Currency
    {
        $currency = Currency::firstOrCreate(
            ['short_name' => 'GHS'],
            ['name' => 'Ghanaian Cedi', 'symbol' => '₵'],
        );

        foreach (self::GHS_DENOMINATIONS as $index => $denomination) {
            $currency->denominations()->firstOrCreate(
                ['value' => $denomination['value'], 'type' => $denomination['type']],
                ['label' => $denomination['label'], 'sort_order' => $index + 1],
            );
        }

        return $currency;
    }

    protected function createDefaultCashbook(Branch $branch, Currency $currency): Cashbook
    {
        return Cashbook::firstOrCreate(
            ['branch_id' => $branch->id, 'currency_id' => $currency->id],
            ['balance' => '0.00'],
        );
    }

    protected function createDefaultCashBalanceThreshold(Branch $branch, User $adminUser): void
    {
        CashBalanceThreshold::firstOrCreate(
            ['branch_id' => $branch->id],
            [
                'threshold_amount' => self::DEFAULT_CASH_BALANCE_THRESHOLD_AMOUNT,
                'notification_user_ids' => [$adminUser->id],
                'cooldown_minutes' => 1440,
                'is_active' => true,
            ],
        );
    }

    protected function createDefaultSettings(Branch $branch, Role $adminRole): void
    {
        $defaultAdvanceCostCode = CostCode::where('code', self::DEFAULT_ADVANCE_COST_CODE)->first();

        if ($defaultAdvanceCostCode instanceof CostCode) {
            $this->settingsService->setDefaultAdvanceCostCode($defaultAdvanceCostCode->id);
        }

        $this->settingsService->setRequireExpenseSourceDocuments(true);
        $this->settingsService->setRequireRetirementSourceDocuments(true);

        $this->settingsService->setRetirementReminderSettings([
            'grace_period_days' => self::RETIREMENT_REMINDER_GRACE_PERIOD_DAYS,
            'frequency_days' => self::RETIREMENT_REMINDER_FREQUENCY_DAYS,
            'notify_submitter' => true,
            'notify_approvers' => true,
            'notify_role_ids' => [(int) $adminRole->id],
        ]);

        $this->settingsService->setSsoDefaultBranch($branch->id);
    }

    protected function createDefaultWorkflowTemplates(): void
    {
        foreach (PaymentRequestType::cases() as $type) {
            $template = WorkflowTemplate::create([
                'name' => ucfirst($type->value) . ' Approval',
                'type' => $type->value,
                'branch_id' => null,
            ]);

            foreach (self::DEFAULT_WORKFLOW_STAGE_ROLES as $index => $roleName) {
                $stage = WorkflowStage::create([
                    'workflow_template_id' => $template->id,
                    'name' => $roleName,
                    'display_order' => $index + 1,
                ]);

                $role = Role::where('name', $roleName)->where('guard_name', 'web')->first();

                if ($role instanceof Role) {
                    $stage->roles()->attach($role->id);
                }
            }
        }
    }

    protected function createPermissions(): void
    {
        foreach (PermissionKey::cases() as $key) {
            Permission::create(['name' => $key->value, 'guard_name' => 'web']);
        }
    }

    protected function createDefaultRoles(): void
    {
        foreach (self::DEFAULT_ROLE_PERMISSIONS as $roleName => $permissionKeys) {
            $role = Role::create(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo(array_map(
                static fn(PermissionKey $key): string => $key->value,
                $permissionKeys,
            ));
        }
    }

    public function createTenant(string $subdomain, string $name, string $adminEmail, string $adminPassword, string|null $idpTenantId = null): Tenant
    {
        $tenant = Tenant::create(['id' => $subdomain, 'name' => $name, 'idp_tenant_id' => $idpTenantId]);
        $tenant->domains()->create([
            'domain' => $subdomain . '.' . parse_url(config()->string('app.url'), PHP_URL_HOST),
        ]);

        $tenant->run(fn() => $this->handle($tenant, $adminEmail, $adminPassword));

        return $tenant;
    }
}
