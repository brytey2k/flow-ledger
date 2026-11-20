<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\Tenant\CashbookEntryDto;
use App\DTOs\Tenant\CashCountDto;
use App\DTOs\Tenant\CreatePaymentRequestDto;
use App\DTOs\Tenant\CreateRetirementRequestDto;
use App\DTOs\Tenant\DisbursePaymentRequestDto;
use App\DTOs\Tenant\PaymentRequestItemDto;
use App\DTOs\Tenant\RetirementRequestItemDto;
use App\DTOs\Tenant\WorkflowStageDto;
use App\Enums\FeatureFlag;
use App\Enums\Tenant\CurrencyDenominationType;
use App\Enums\Tenant\PaymentRequestType;
use App\Enums\Tenant\PermissionKey;
use App\Enums\Tenant\UserStatus;
use App\Interfaces\FeatureFlagServiceInterface;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\BranchClosure;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use App\Models\Tenant\Department;
use App\Models\Tenant\Level;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Position;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowTemplate;
use Carbon\CarbonImmutable;
use Closure;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Spatie\Permission\PermissionRegistrar;
use Stancl\Tenancy\Jobs\DeleteDatabase;

/**
 * Builds the fictional "Savanna Relief Trust" demo tenant used for user-guide screenshots.
 *
 * Everything here is made up: the organisation, the people and the numbers. Every demo
 * login uses the local-only test password {@see self::PASSWORD}; the administrator is
 * {@see self::ADMIN_EMAIL}. Requests are driven through the real services (payment
 * requests, workflow engine, retirements, cashbook, cash counts) with the clock moved
 * back in time, so approval history, cashbook entries and the activity log are consistent
 * and spread over the last three months.
 *
 * @phpstan-type DemoStep array{0: string, 1?: string, 2?: string}
 * @phpstan-type DemoItem array{0: string, 1: float, 2: string, 3?: string}
 * @phpstan-type DemoRetirement array{start: int, notes: string, items: list<DemoItem>, flow: list<DemoStep>}
 * @phpstan-type DemoScenario array{key: string, type: string, requester: string, notes: string, start: int, items: list<DemoItem>, flow: list<DemoStep>, retirement?: DemoRetirement}
 * @phpstan-type DemoSummaryRow array{key: string, id: int, type: string, status: string, requester: string, branch: string, amount: string, notes: string, retirement_id: int|null, retirement_status: string|null}
 */
class DemoDataService
{
    public const string TENANT_ID = 'demo';

    public const string TENANT_NAME = 'Savanna Relief Trust';

    public const string ADMIN_EMAIL = 'admin@demo.test';

    /** Local, fictional test password shared by every demo login. */
    public const string PASSWORD = 'password';

    private const string EMAIL_DOMAIN = 'demo.test';

    private const string ADMIN_ROLE = '__system_admin__';

    private const string HEAD_OF_DEPARTMENT_ROLE = 'Head of Department';

    private const string STAFF_ROLE = 'Staff';

    private const string FIRST_REQUESTER = 'kwabena.ofori';

    /** @var array<string, list<PermissionKey>> */
    private const array ROLE_PERMISSIONS = [
        'Finance Officer' => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::ApproveRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::CreateRetirementRequest,
            PermissionKey::ViewDescendantBranches,
            PermissionKey::AccessCashbook,
            PermissionKey::AccessReports,
        ],
        'Finance Manager' => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::ApproveRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::CreateRetirementRequest,
            PermissionKey::ViewDescendantBranches,
            PermissionKey::AccessCashbook,
            PermissionKey::AccessCashCount,
            PermissionKey::AccessReports,
        ],
        'Finance Director' => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::ApproveRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::CreateRetirementRequest,
            PermissionKey::ViewDescendantBranches,
            PermissionKey::AccessCashbook,
            PermissionKey::AccessCashCount,
            PermissionKey::AccessReports,
            PermissionKey::AccessActivityLog,
        ],
        'Disbursement Officer' => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::ApproveRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::DisburseRequests,
            PermissionKey::SettleRetirements,
            PermissionKey::ViewDescendantBranches,
            PermissionKey::AccessCashbook,
            PermissionKey::CreateCashbookEntry,
            PermissionKey::AccessCashCount,
            PermissionKey::CreateCashCount,
        ],
        self::HEAD_OF_DEPARTMENT_ROLE => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::ApproveRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::EditPaymentRequest,
            PermissionKey::CreateRetirementRequest,
            PermissionKey::EditRetirementRequest,
            PermissionKey::ViewDescendantBranches,
        ],
        self::STAFF_ROLE => [
            PermissionKey::AccessPaymentRequests,
            PermissionKey::CreatePaymentRequest,
            PermissionKey::EditPaymentRequest,
            PermissionKey::AccessRetirementRequests,
            PermissionKey::CreateRetirementRequest,
            PermissionKey::EditRetirementRequest,
        ],
    ];

    /** @var list<string> */
    private const array DEPARTMENTS = [
        'Finance & Accounts',
        'Programmes',
        'Operations',
        'Human Resources',
        'Administration',
        'Procurement',
    ];

    /** @var list<string> */
    private const array POSITIONS = [
        'Executive Director',
        'Finance Director',
        'Finance Manager',
        'Finance Officer',
        'Cashier',
        'Programme Manager',
        'Programme Officer',
        'Field Officer',
        'Operations Manager',
        'Driver',
        'HR Manager',
        'HR Officer',
        'Procurement Manager',
        'Procurement Officer',
        'Administration Manager',
        'Administrative Officer',
        'Systems Administrator',
        'Office Assistant',
    ];

    /** @var array<string, array{name: string, department: string}> */
    private const array COST_CODES = [
        'TRV-001' => ['name' => 'Travel & Per Diem', 'department' => 'Programmes'],
        'COM-001' => ['name' => 'Community Outreach', 'department' => 'Programmes'],
        'FUE-001' => ['name' => 'Fuel', 'department' => 'Operations'],
        'VEH-001' => ['name' => 'Vehicle Maintenance', 'department' => 'Operations'],
        'OFS-001' => ['name' => 'Office Supplies', 'department' => 'Administration'],
        'TRN-001' => ['name' => 'Training & Workshops', 'department' => 'Human Resources'],
        'PRC-002' => ['name' => 'Market Surveys & Supplier Visits', 'department' => 'Procurement'],
    ];

    /**
     * Branch code => [name, level position, parent code, opening cash, low-cash threshold].
     *
     * @var array<string, array{0: string, 1: int, 2: string|null, 3: string|null, 4: string|null}>
     */
    private const array BRANCHES = [
        'HO' => ['Head Office', 1, null, '40000.00', '10000.00'],
        'CZ' => ['Coastal Zone', 2, 'HO', null, null],
        'NZ' => ['Ashanti & Northern Zone', 2, 'HO', null, null],
        'ACC' => ['Accra', 3, 'CZ', '20000.00', '5000.00'],
        'TKD' => ['Takoradi', 3, 'CZ', '8000.00', '3000.00'],
        'KSI' => ['Kumasi', 3, 'NZ', '25000.00', '5000.00'],
        'TML' => ['Tamale', 3, 'NZ', '12000.00', '8000.00'],
    ];

    /**
     * Email local part => [first name, last name, role, branch code, department, position].
     *
     * @var array<string, array{0: string, 1: string, 2: string, 3: string, 4: string, 5: string}>
     */
    private const array USERS = [
        'admin' => ['Adwoa', 'Sarpong', self::ADMIN_ROLE, 'HO', 'Administration', 'Systems Administrator'],
        'efua.owusu' => ['Efua', 'Owusu', 'Finance Director', 'HO', 'Finance & Accounts', 'Finance Director'],
        'kwame.asante' => ['Kwame', 'Asante', 'Finance Manager', 'HO', 'Finance & Accounts', 'Finance Manager'],
        'ama.mensah' => ['Ama', 'Mensah', 'Finance Officer', 'HO', 'Finance & Accounts', 'Finance Officer'],
        'kofi.boateng' => ['Kofi', 'Boateng', 'Disbursement Officer', 'HO', 'Finance & Accounts', 'Cashier'],
        'serwaa.amponsah' => ['Serwaa', 'Amponsah', 'Disbursement Officer', 'KSI', 'Finance & Accounts', 'Cashier'],
        'abena.darko' => ['Abena', 'Darko', self::HEAD_OF_DEPARTMENT_ROLE, 'HO', 'Programmes', 'Programme Manager'],
        'yaw.owusu' => ['Yaw', 'Owusu', self::HEAD_OF_DEPARTMENT_ROLE, 'HO', 'Operations', 'Operations Manager'],
        'akua.addo' => ['Akua', 'Addo', self::HEAD_OF_DEPARTMENT_ROLE, 'HO', 'Human Resources', 'HR Manager'],
        'kojo.antwi' => ['Kojo', 'Antwi', self::HEAD_OF_DEPARTMENT_ROLE, 'HO', 'Procurement', 'Procurement Manager'],
        'ekow.hammond' => ['Ekow', 'Hammond', self::HEAD_OF_DEPARTMENT_ROLE, 'HO', 'Administration', 'Administration Manager'],
        'kwabena.ofori' => ['Kwabena', 'Ofori', self::STAFF_ROLE, 'TML', 'Programmes', 'Programme Officer'],
        'esi.quaye' => ['Esi', 'Quaye', self::STAFF_ROLE, 'KSI', 'Programmes', 'Field Officer'],
        'afia.boakye' => ['Afia', 'Boakye', self::STAFF_ROLE, 'ACC', 'Programmes', 'Field Officer'],
        'nana.yeboah' => ['Nana', 'Yeboah', self::STAFF_ROLE, 'ACC', 'Operations', 'Driver'],
        'adjoa.tetteh' => ['Adjoa', 'Tetteh', self::STAFF_ROLE, 'TKD', 'Administration', 'Administrative Officer'],
        'fiifi.quansah' => ['Fiifi', 'Quansah', self::STAFF_ROLE, 'HO', 'Procurement', 'Procurement Officer'],
        'yaa.agyeman' => ['Yaa', 'Agyeman', self::STAFF_ROLE, 'HO', 'Human Resources', 'HR Officer'],
    ];

    /**
     * Staff records without a login: [first name, last name, branch code, department, position].
     *
     * @var list<array{0: string, 1: string, 2: string, 3: string, 4: string}>
     */
    private const array STAFF_WITHOUT_LOGIN = [
        ['Kwesi', 'Appiah', 'TML', 'Operations', 'Driver'],
        ['Mavis', 'Ampofo', 'KSI', 'Administration', 'Office Assistant'],
        ['Gifty', 'Nyarko', 'TKD', 'Programmes', 'Field Officer'],
        ['Kweku', 'Mensah', 'HO', 'Administration', 'Executive Director'],
    ];

    private Carbon $realNow;

    private Carbon $clock;

    private int $stepCounter = 0;

    /** @var array<string, User> */
    private array $users = [];

    /** @var array<string, Branch> */
    private array $branches = [];

    /** @var array<string, int> */
    private array $costCodeIds = [];

    public function __construct(
        private readonly NewTenantSetupService $tenantSetup,
        private readonly PaymentRequestService $paymentRequests,
        private readonly RetirementService $retirements,
        private readonly WorkflowEngineService $engine,
        private readonly WorkflowStageService $workflowStages,
        private readonly WorkflowTemplateVersioningService $workflowVersioning,
        private readonly CashbookService $cashbooks,
        private readonly CashCountService $cashCounts,
        private readonly AttachmentService $attachments,
        private readonly DashboardCacheService $dashboardCache,
    ) {}

    /**
     * Create the demo tenant through the normal new-tenant setup, or reuse it when it
     * already exists. With $fresh the existing demo tenant, its database and its
     * attachment files are deleted first. A tenant with the demo id that was not
     * created by this service is never touched.
     *
     * @param bool $fresh
     */
    public function provisionTenant(bool $fresh): Tenant
    {
        $existing = Tenant::find(self::TENANT_ID);

        if ($existing instanceof Tenant && $existing->getAttribute('is_demo') !== true && $existing->getAttribute('name') !== self::TENANT_NAME) {
            throw new RuntimeException('A tenant with id "' . self::TENANT_ID . '" exists but was not created by demo:seed, so it is left alone.');
        }

        if ($existing instanceof Tenant && $fresh) {
            $this->deleteTenant($existing);
            $existing = null;
        }

        if ($existing instanceof Tenant) {
            return $existing;
        }

        $tenant = $this->tenantSetup->createTenant(self::TENANT_ID, self::TENANT_NAME, self::ADMIN_EMAIL, self::PASSWORD);
        $tenant->setAttribute('is_demo', true);
        $tenant->save();

        // The demo is signed into with email and password, never through the identity provider.
        $featureFlags = app(FeatureFlagServiceInterface::class);
        $featureFlags->activate(FeatureFlag::LocalAuth, $tenant);
        $featureFlags->deactivate(FeatureFlag::VerifyLoginWithIdp, $tenant);
        $featureFlags->deactivate(FeatureFlag::DelegateIdentityToIdp, $tenant);

        return $tenant;
    }

    /**
     * Seed the demo data into the current tenant. Reference data (branches, people,
     * roles, workflows, cost codes) is created or topped up on every run; the requests,
     * retirements, cashbook movements and cash counts are only created once.
     *
     * @return array{created: bool, requests: list<DemoSummaryRow>}
     */
    public function seed(): array
    {
        $this->realNow = Carbon::now()->copy();
        $this->clock = $this->realNow->copy();
        $this->stepCounter = 0;

        $alreadySeeded = PaymentRequest::query()
            ->whereHas('staff', fn($query) => $query->where('email', $this->email(self::FIRST_REQUESTER)))
            ->exists();

        try {
            $this->travelTo($this->realNow->copy()->subDays(100)->setTime(8, 30));

            $this->seedRoles();
            $currency = $this->seedCurrencies();
            $this->seedBranches($currency);
            $this->seedDepartmentsPositionsAndCostCodes();
            $this->seedUsersAndStaff();
            $this->seedWorkflowTemplates();

            if (! $alreadySeeded) {
                $this->seedCashbooks();

                foreach ($this->scenarios() as $scenario) {
                    $this->runScenario($scenario);
                }

                $this->seedCashCounts();
            }
        } finally {
            $this->travelTo(null);
            $this->signOut();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }

        $this->dashboardCache->invalidateForCurrentTenant();

        return ['created' => ! $alreadySeeded, 'requests' => $this->summary()];
    }

    private function deleteTenant(Tenant $tenant): void
    {
        $tenant->run(function (): void {
            Storage::disk('local')->deleteDirectory('payment-requests');
            Storage::disk('local')->deleteDirectory('retirements');
        });

        dispatch_sync(new DeleteDatabase($tenant));

        $tenant->domains()->delete();
        $tenant->delete();
    }

    private function seedRoles(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionKey::cases() as $key) {
            Permission::firstOrCreate(['name' => $key->value, 'guard_name' => 'web']);
        }

        $adminRole = Role::firstOrCreate(['name' => config()->string('roles.system_admin_name'), 'guard_name' => 'web']);
        $adminRole->givePermissionTo(Permission::all());

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissionKeys) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->givePermissionTo(array_map(static fn(PermissionKey $key): string => $key->value, $permissionKeys));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedCurrencies(): Currency
    {
        $cedi = Currency::firstOrCreate(['short_name' => 'GHS'], ['name' => 'Ghanaian Cedi', 'symbol' => '₵']);

        $cediDenominations = [
            ['200.00', 'GHS 200 Note', CurrencyDenominationType::Note],
            ['100.00', 'GHS 100 Note', CurrencyDenominationType::Note],
            ['50.00', 'GHS 50 Note', CurrencyDenominationType::Note],
            ['20.00', 'GHS 20 Note', CurrencyDenominationType::Note],
            ['10.00', 'GHS 10 Note', CurrencyDenominationType::Note],
            ['5.00', 'GHS 5 Note', CurrencyDenominationType::Note],
            ['2.00', 'GHS 2 Note', CurrencyDenominationType::Note],
            ['1.00', 'GHS 1 Coin', CurrencyDenominationType::Coin],
            ['0.50', '50 Pesewas Coin', CurrencyDenominationType::Coin],
            ['0.20', '20 Pesewas Coin', CurrencyDenominationType::Coin],
            ['0.10', '10 Pesewas Coin', CurrencyDenominationType::Coin],
        ];

        $this->seedDenominations($cedi, $cediDenominations);

        $dollar = Currency::firstOrCreate(['short_name' => 'USD'], ['name' => 'US Dollar', 'symbol' => '$']);
        $this->seedDenominations($dollar, [
            ['100.00', 'USD 100 Note', CurrencyDenominationType::Note],
            ['50.00', 'USD 50 Note', CurrencyDenominationType::Note],
            ['20.00', 'USD 20 Note', CurrencyDenominationType::Note],
            ['10.00', 'USD 10 Note', CurrencyDenominationType::Note],
            ['5.00', 'USD 5 Note', CurrencyDenominationType::Note],
            ['1.00', 'USD 1 Note', CurrencyDenominationType::Note],
        ]);

        return $cedi;
    }

    /** @param list<array{0: string, 1: string, 2: CurrencyDenominationType}> $denominations */
    private function seedDenominations(Currency $currency, array $denominations): void
    {
        foreach ($denominations as $index => [$value, $label, $type]) {
            $currency->denominations()->firstOrCreate(
                ['value' => $value, 'type' => $type],
                ['label' => $label, 'sort_order' => $index + 1],
            );
        }
    }

    private function seedBranches(Currency $currency): void
    {
        $levelNames = [1 => 'Head Office', 2 => 'Zone', 3 => 'Branch'];
        $levels = [];

        foreach ($levelNames as $position => $name) {
            $level = Level::where('position', $position)->first() ?? Level::create(['name' => $name, 'position' => $position]);
            $level->update(['name' => $name]);
            $levels[$position] = $level;
        }

        foreach (self::BRANCHES as $code => [$name, $levelPosition, $parentCode]) {
            $level = $levels[$levelPosition];
            $branch = Branch::where('code', $code)->first()
                ?? Branch::where('name', $name)->where('level_id', $level->id)->first();

            if ($branch instanceof Branch) {
                $branch->update(['code' => $code, 'currency_id' => $branch->currency_id ?? $currency->id]);
                $this->branches[$code] = $branch;

                continue;
            }

            $branch = new Branch(['name' => $name, 'code' => $code, 'level_id' => $level->id, 'currency_id' => $currency->id]);

            if ($parentCode === null) {
                $maxPosition = Branch::whereNull('parent_id')->max('position');
                $branch->position = (is_int($maxPosition) ? $maxPosition : -1) + 1;
                $branch->save();
                BranchClosure::firstOrCreate(['ancestor_id' => $branch->id, 'descendant_id' => $branch->id, 'depth' => 0]);
            } else {
                $this->branches[$parentCode]->addChild($branch);
            }

            $this->branches[$code] = $branch->refresh();
        }
    }

    private function seedDepartmentsPositionsAndCostCodes(): void
    {
        foreach (self::DEPARTMENTS as $name) {
            Department::firstOrCreate(['name' => $name]);
        }

        foreach (self::POSITIONS as $name) {
            Position::firstOrCreate(['name' => $name]);
        }

        foreach (self::COST_CODES as $code => $definition) {
            CostCode::firstOrCreate(
                ['code' => $code],
                ['name' => $definition['name'], 'department_id' => $this->departmentId($definition['department'])],
            );
        }

        $this->costCodeIds = [];

        foreach (CostCode::all(['id', 'code']) as $costCode) {
            $this->costCodeIds[$costCode->code] = $costCode->id;
        }
    }

    private function seedUsersAndStaff(): void
    {
        $adminRoleName = config()->string('roles.system_admin_name');

        foreach (self::USERS as $key => [$firstName, $lastName, $roleName, $branchCode, $department, $position]) {
            $branch = $this->branches[$branchCode];
            $email = $this->email($key);

            $user = User::withTrashed()->where('email', $email)->first() ?? new User(['email' => $email]);
            $user->fill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => Hash::make(self::PASSWORD),
                'branch_id' => $branch->id,
                'operational_branch_id' => $branch->id,
                'status' => UserStatus::Active,
                'activated_at' => $user->activated_at ?? now(),
            ]);
            $user->forceFill(['email_verified_at' => $user->email_verified_at ?? now(), 'deleted_at' => null]);
            $user->save();
            $user->syncRoles([$roleName === self::ADMIN_ROLE ? $adminRoleName : $roleName]);

            $staff = Staff::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'email' => $email,
                    'phone' => $this->phoneNumber(count($this->users) + 1),
                    'department_id' => $this->departmentId($department),
                    'position_id' => $this->positionId($position),
                    'branch_id' => $branch->id,
                ],
            );

            $user->setRelation('staffProfile', $staff);
            $this->users[$key] = $user;
        }

        foreach (self::STAFF_WITHOUT_LOGIN as $index => [$firstName, $lastName, $branchCode, $department, $position]) {
            Staff::firstOrCreate(
                ['first_name' => $firstName, 'last_name' => $lastName, 'user_id' => null],
                [
                    'email' => null,
                    'phone' => $this->phoneNumber(50 + $index),
                    'department_id' => $this->departmentId($department),
                    'position_id' => $this->positionId($position),
                    'branch_id' => $this->branches[$branchCode]->id,
                ],
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Advance and expense requests go head of department (own department only) → finance
     * officer → finance manager (GHS 1,000 and above) → finance director (GHS 5,000 and
     * above), then to a disbursement officer. Retirements go head of department → finance
     * officer → finance manager (GHS 2,000 and above), then to settlement. A template that
     * already has workflow instances is left as it is, as the app itself would fork it.
     */
    private function seedWorkflowTemplates(): void
    {
        $hod = $this->roleId(self::HEAD_OF_DEPARTMENT_ROLE);
        $officer = $this->roleId('Finance Officer');
        $manager = $this->roleId('Finance Manager');
        $director = $this->roleId('Finance Director');

        $requestStages = [
            new WorkflowStageDto('Head of Department Review', 1, null, null, [$hod], scopeToDepartment: true, allowDocumentRequests: true),
            new WorkflowStageDto('Finance Officer Review', 2, null, null, [$officer]),
            new WorkflowStageDto('Finance Manager Approval', 3, 1000.0, null, [$manager]),
            new WorkflowStageDto('Finance Director Approval', 4, 5000.0, null, [$director], allowSendBack: true),
        ];

        $retirementStages = [
            new WorkflowStageDto('Head of Department Review', 1, null, null, [$hod], scopeToDepartment: true),
            new WorkflowStageDto('Finance Officer Review', 2, null, null, [$officer]),
            new WorkflowStageDto('Finance Manager Approval', 3, 2000.0, null, [$manager]),
        ];

        $definitions = [
            PaymentRequestType::Advance->value => ['Advance Approval', $requestStages],
            PaymentRequestType::Expense->value => ['Expense Approval', $requestStages],
            PaymentRequestType::Retirement->value => ['Retirement Approval', $retirementStages],
        ];

        foreach ($definitions as $type => [$name, $stages]) {
            $template = WorkflowTemplate::where('type', $type)->whereNull('branch_id')->where('is_current', true)->first()
                ?? WorkflowTemplate::create(['name' => $name, 'type' => $type, 'branch_id' => null]);

            if ($this->workflowVersioning->shouldFork($template, isStructuralChange: true)) {
                continue;
            }

            $template->update(['name' => $name]);
            $template->stages()->get()->each(fn(Model $stage): bool|null => $stage->delete());

            foreach ($stages as $stage) {
                $this->workflowStages->create($template, $stage);
            }
        }
    }

    private function seedCashbooks(): void
    {
        $this->travelTo($this->realNow->copy()->subDays(95)->setTime(9, 0));
        $kofi = $this->users['kofi.boateng'];

        foreach (self::BRANCHES as $code => [, , , $openingCash, $threshold]) {
            if ($openingCash === null || $threshold === null) {
                continue;
            }

            $branch = $this->branches[$code];
            $cashbook = $this->cashbooks->getOrCreateForBranch($branch);

            CashBalanceThreshold::updateOrCreate(
                ['branch_id' => $branch->id],
                [
                    'threshold_amount' => $threshold,
                    'notification_user_ids' => [$kofi->id, $this->users['kwame.asante']->id],
                    'cooldown_minutes' => 1440,
                    'is_active' => true,
                ],
            );

            $this->actingAs($kofi, fn() => $this->cashbooks->recordManualReceipt($cashbook, new CashbookEntryDto(
                $openingCash,
                today(),
                'OB-' . $code . '-' . today()->format('Y'),
                'Opening cash balance transferred from the bank imprest account.',
            ), $kofi));
        }

        $replenishments = [
            ['HO', 45, '15000.00', 'CHQ-004512', 'Cash replenishment from Ecobank current account.'],
            ['ACC', 40, '5000.00', 'CHQ-004519', 'Imprest top-up for Accra field operations.'],
        ];

        foreach ($replenishments as [$code, $daysAgo, $amount, $reference, $notes]) {
            $this->travelTo($this->realNow->copy()->subDays($daysAgo)->setTime(10, 20));
            $cashbook = $this->cashbooks->getOrCreateForBranch($this->branches[$code]);
            $this->actingAs($kofi, fn() => $this->cashbooks->recordManualReceipt($cashbook, new CashbookEntryDto(
                $amount,
                today(),
                $reference,
                $notes,
            ), $kofi));
        }
    }

    private function seedCashCounts(): void
    {
        $counts = [
            ['KSI', 'serwaa.amponsah', 30, '0.00', 'Monthly surprise count witnessed by the zonal finance officer. Cash agrees to the cashbook.'],
            ['HO', 'kofi.boateng', 1, '-20.00', 'Month-end count. GHS 20.00 short; a missing petty cash voucher is being traced.'],
        ];

        foreach ($counts as [$code, $counter, $daysAgo, $variance, $notes]) {
            $this->travelTo($this->realNow->copy()->subDays($daysAgo)->setTime(16, 30));
            $cashbook = $this->cashbooks->getOrCreateForBranch($this->branches[$code])->refresh();
            $balance = is_numeric($cashbook->balance) ? (float) $cashbook->balance : 0.0;
            $user = $this->users[$counter];

            $this->actingAs($user, fn() => $this->cashCounts->store(
                $cashbook,
                new CashCountDto($cashbook->id, $notes, $this->denominationBreakdown($cashbook, $balance + (float) $variance)),
                $user,
            ));
        }
    }

    /** @return array<int, array{denomination_id: int, quantity: int}> */
    private function denominationBreakdown(Cashbook $cashbook, float $amount): array
    {
        $remaining = (int) round($amount * 100);
        $items = [];

        $denominations = CurrencyDenomination::where('currency_id', $cashbook->currency_id)
            ->get()
            ->sortByDesc(fn(CurrencyDenomination $denomination): float => (float) $denomination->value);

        foreach ($denominations as $denomination) {
            $valueInPesewas = (int) round((float) $denomination->value * 100);

            if ($valueInPesewas <= 0 || $remaining < $valueInPesewas) {
                continue;
            }

            $quantity = intdiv($remaining, $valueInPesewas);
            $remaining -= $quantity * $valueInPesewas;
            $items[] = ['denomination_id' => $denomination->id, 'quantity' => $quantity];
        }

        return $items;
    }

    /** @param DemoScenario $scenario */
    private function runScenario(array $scenario): void
    {
        $requester = $this->users[$scenario['requester']];
        $staff = $requester->staffProfile;
        assert($staff instanceof Staff);

        $this->travelTo($this->businessMoment($this->realNow->copy()->subDays($scenario['start'])));

        $paymentRequest = $this->actingAs($requester, fn(): PaymentRequest => $this->paymentRequests->createDraft(new CreatePaymentRequestDto(
            $staff->id,
            $staff->branch_id ?? $requester->branch_id,
            $scenario['type'],
            $scenario['notes'],
            array_map(fn(array $item): PaymentRequestItemDto => new PaymentRequestItemDto(
                $item[0],
                $item[1],
                $this->costCodeIds[$item[2]],
                $item[3] ?? null,
            ), $scenario['items']),
        ), $requester));

        if ($scenario['type'] === PaymentRequestType::Expense->value) {
            $this->attachReceipt($paymentRequest, $requester, $scenario['notes'], $scenario['items']);
        }

        foreach ($scenario['flow'] as $step) {
            $this->runStep($paymentRequest, $requester, $step);
        }

        if (! isset($scenario['retirement'])) {
            return;
        }

        $retirementDefinition = $scenario['retirement'];
        $this->travelTo($this->businessMoment($this->realNow->copy()->subDays($retirementDefinition['start'])));

        $retirement = $this->actingAs($requester, fn(): RetirementRequest => $this->retirements->createDraft(
            $paymentRequest->refresh(),
            new CreateRetirementRequestDto(
                $retirementDefinition['notes'],
                false,
                array_map(fn(array $item): RetirementRequestItemDto => new RetirementRequestItemDto(
                    $item[0],
                    $item[1],
                    $this->costCodeIds[$item[2]],
                    $item[3] ?? null,
                ), $retirementDefinition['items']),
            ),
            $requester,
        ));

        $this->attachReceipt($retirement, $requester, 'Retirement – ' . $scenario['notes'], $retirementDefinition['items']);

        foreach ($retirementDefinition['flow'] as $step) {
            $this->runStep($retirement, $requester, $step);
        }
    }

    /** @param DemoStep $step */
    private function runStep(PaymentRequest|RetirementRequest $subject, User $requester, array $step): void
    {
        $action = $step[0];
        $actor = isset($step[1]) && $step[1] !== '' ? $this->users[$step[1]] : $requester;
        $text = $step[2] ?? null;

        $this->tick($action);
        $subject->refresh();

        $this->actingAs($actor, function () use ($subject, $requester, $actor, $action, $text): void {
            match ($action) {
                'submit' => $subject instanceof RetirementRequest
                    ? $this->retirements->submit($subject, $actor)
                    : $this->paymentRequests->submit($subject, $actor),
                'approve' => $this->engine->approve($this->actionableStage($subject, $actor), $actor, $text),
                'send_back' => $this->engine->sendBack($this->actionableStage($subject, $actor), $actor, (string) $text),
                'reject' => $this->engine->reject($this->actionableStage($subject, $actor), $actor, (string) $text),
                'decline' => $this->decline($subject, $actor, (string) $text),
                'resubmit' => $this->resubmit($subject, $requester),
                'cancel' => $subject instanceof RetirementRequest
                    ? $this->retirements->cancel($subject, $actor)
                    : $this->paymentRequests->cancel($subject, $actor),
                'comment' => $subject->comments()->create(['user_id' => $actor->id, 'body' => (string) $text]),
                'disburse' => $subject instanceof PaymentRequest
                    ? $this->paymentRequests->disburse($subject, new DisbursePaymentRequestDto($text), $actor)
                    : throw new RuntimeException('Only payment requests can be disbursed.'),
                'settle' => $subject instanceof RetirementRequest
                    ? $this->retirements->settle($subject, $text, $actor)
                    : throw new RuntimeException('Only retirements can be settled.'),
                default => throw new RuntimeException("Unknown demo step [{$action}]."),
            };
        });
    }

    private function decline(PaymentRequest|RetirementRequest $subject, User $actor, string $reason): void
    {
        if (! $subject instanceof PaymentRequest) {
            throw new RuntimeException('Only payment requests can be declined.');
        }

        // Same guard as PaymentRequestDeclineController: only someone who can act on the active stage may decline.
        $this->actionableStage($subject, $actor);
        $subject->comments()->create(['user_id' => $actor->id, 'body' => $reason]);
        $this->paymentRequests->decline($subject, $actor);
    }

    private function resubmit(PaymentRequest|RetirementRequest $subject, User $requester): void
    {
        $this->attachReceipt($subject, $requester, 'Additional receipts', [['Missing receipts now attached', 0.0, 'OFS-001']]);
        $this->engine->resubmitAfterFix($subject, $requester);
    }

    private function actionableStage(PaymentRequest|RetirementRequest $subject, User $actor): WorkflowInstanceStage
    {
        $instance = WorkflowInstance::where('workflowable_type', $subject::class)
            ->where('workflowable_id', $subject->id)
            ->where('status', 'in_progress')
            ->latest('id')
            ->first();

        $stage = $instance?->instanceStages()
            ->where('status', 'active')
            ->get()
            ->first(fn(WorkflowInstanceStage $candidate): bool => $this->engine->canUserActOnStage($candidate, $actor));

        if (! $stage instanceof WorkflowInstanceStage) {
            throw new RuntimeException(sprintf(
                'Demo seed: %s cannot act on %s #%d (status %s).',
                $actor->email,
                class_basename($subject),
                $subject->id,
                $subject->status,
            ));
        }

        return $stage;
    }

    /** @param list<DemoItem> $items */
    private function attachReceipt(PaymentRequest|RetirementRequest $subject, User $uploader, string $title, array $items): void
    {
        $lines = [];
        $total = 0.0;

        foreach ($items as $item) {
            if ($item[1] <= 0) {
                $lines[] = $item[0];

                continue;
            }

            $lines[] = sprintf('%s  GHS %s', $item[0], number_format($item[1], 2));
            $total += $item[1];
        }

        if ($total > 0) {
            $lines[] = 'Total: GHS ' . number_format($total, 2);
        }

        $path = tempnam(sys_get_temp_dir(), 'demo-receipt-');

        if ($path === false) {
            return;
        }

        file_put_contents($path, $this->receiptPdf($title, $lines));
        $fileName = sprintf('receipt-%s-%d.pdf', $subject instanceof RetirementRequest ? 'retirement' : 'request', $subject->id);

        try {
            $this->attachments->storeEditableRequestAttachment(
                $subject,
                new UploadedFile($path, $fileName, 'application/pdf', null, true),
                $uploader,
            );
        } finally {
            @unlink($path);
        }
    }

    /**
     * A one-page PDF marked as fictional demo data, so attachment previews have something to show.
     *
     * @param string $title
     * @param list<string> $lines
     */
    private function receiptPdf(string $title, array $lines): string
    {
        $escape = static fn(string $text): string => str_replace(
            ['\\', '(', ')'],
            ['\\\\', '\\(', '\\)'],
            (string) preg_replace('/[^\x20-\x7E]/', '-', $text),
        );

        $content = 'BT /F1 16 Tf 36 255 Td (' . $escape(self::TENANT_NAME) . ") Tj ET\n";
        $content .= 'BT /F1 12 Tf 36 235 Td (' . $escape($title) . ") Tj ET\n";
        $content .= 'BT /F1 9 Tf 36 220 Td (' . $escape('Receipt date: ' . now()->format('d M Y')) . ") Tj ET\n";
        $y = 200;

        foreach ($lines as $line) {
            $content .= "BT /F1 10 Tf 36 {$y} Td (" . $escape($line) . ") Tj ET\n";
            $y -= 16;
        }

        $content .= 'BT /F1 8 Tf 36 24 Td (' . $escape('DEMO DOCUMENT - fictional data for screenshots only') . ") Tj ET\n";

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 420 297] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length ' . strlen($content) . " >>\nstream\n{$content}endstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $index => $body) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1) . " 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= 'xref' . "\n0 " . (count($objects) + 1) . "\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf . 'trailer' . "\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF\n";
    }

    /** @return list<DemoSummaryRow> */
    private function summary(): array
    {
        $requests = PaymentRequest::with(['staff', 'branch', 'retirementRequests'])->orderBy('id')->get();
        $rows = [];

        foreach ($requests as $request) {
            $retirement = $request->retirementRequests->sortByDesc('id')->first();
            $key = array_search($request->notes, array_column($this->scenarios(), 'notes', 'key'), true);

            $rows[] = [
                'key' => is_string($key) ? $key : '',
                'id' => $request->id,
                'type' => $request->type,
                'status' => $request->status,
                'requester' => $request->staff !== null ? $request->staff->first_name . ' ' . $request->staff->last_name : '',
                'branch' => $request->branch !== null ? $request->branch->name : '',
                'amount' => number_format((float) $request->total_amount, 2),
                'notes' => (string) $request->notes,
                'retirement_id' => $retirement?->id,
                'retirement_status' => $retirement?->status,
            ];
        }

        return $rows;
    }

    /**
     * Move the clock between steps: submissions follow the draft within the hour, every
     * other step lands on the next working day. The clock never passes the real "now".
     *
     * @param string $action
     */
    private function tick(string $action): void
    {
        $this->stepCounter++;

        $next = in_array($action, ['submit', 'comment', 'resubmit'], true)
            ? $this->clock->copy()->addMinutes(35 + ($this->stepCounter % 4) * 10)
            : $this->businessMoment($this->clock->copy()->addDay());

        $latest = $this->realNow->copy()->subMinutes(5);

        if ($next->greaterThan($latest)) {
            $next = $this->clock->copy()->addMinutes(10)->min($latest);
        }

        $this->travelTo($next->max($this->clock->copy()->addMinute()));
    }

    private function businessMoment(Carbon $day): Carbon
    {
        while ($day->isWeekend()) {
            $day->addDay();
        }

        $hours = [9, 10, 11, 13, 14, 15, 16];
        $moment = $day->setTime($hours[$this->stepCounter % count($hours)], ($this->stepCounter * 17) % 60);

        return $moment->min($this->realNow->copy()->subMinutes(5));
    }

    private function travelTo(Carbon|null $moment): void
    {
        if ($moment !== null) {
            $this->clock = $moment->copy();
        }

        Carbon::setTestNow($moment);
        CarbonImmutable::setTestNow($moment);
    }

    /**
     * Run a step as the given user, so the activity log and audit trail name the right person.
     *
     * @template TResult
     *
     * @param User $user
     * @param Closure(): TResult $callback
     *
     * @return TResult
     */
    private function actingAs(User $user, Closure $callback): mixed
    {
        Auth::guard('web')->setUser($user);

        try {
            return $callback();
        } finally {
            $this->signOut();
        }
    }

    private function signOut(): void
    {
        $guard = Auth::guard('web');

        if ($guard instanceof SessionGuard) {
            $guard->forgetUser();
        }
    }

    private function email(string $key): string
    {
        return $key === 'admin' ? self::ADMIN_EMAIL : $key . '@' . self::EMAIL_DOMAIN;
    }

    private function phoneNumber(int $index): string
    {
        return sprintf('+233 20 000 %04d', $index);
    }

    private function departmentId(string $name): int
    {
        return Department::where('name', $name)->firstOrFail()->id;
    }

    private function positionId(string $name): int
    {
        return Position::where('name', $name)->firstOrFail()->id;
    }

    private function roleId(string $name): int
    {
        $id = Role::where('name', $name)->where('guard_name', 'web')->firstOrFail()->id;

        return (int) $id;
    }

    /**
     * The demo requests. "start" is how many days ago the draft was raised; each step
     * then lands on the following working day. Amounts are in GHS. Finance manager
     * approval applies from GHS 1,000 (GHS 2,000 for retirements) and finance director
     * approval from GHS 5,000, so the approvers listed match the workflow thresholds.
     *
     * @return list<DemoScenario>
     */
    private function scenarios(): array
    {
        return [
            [
                'key' => 'retired-refund',
                'type' => 'advance',
                'requester' => 'kwabena.ofori',
                'notes' => 'Field monitoring visit to Tamale district projects',
                'start' => 88,
                'items' => [
                    ['Per diem – 4 days @ GHS 250', 1000.0, 'TRV-001'],
                    ['Fuel – Tamale, Yendi and Savelugu loop', 650.0, 'FUE-001'],
                    ['Guest house – 3 nights', 1150.0, 'TRV-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'abena.darko', 'Visit is in the approved Q3 monitoring plan.'],
                    ['approve', 'ama.mensah', 'Per diem rates checked against the travel policy.'],
                    ['approve', 'kwame.asante'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0101'],
                ],
                'retirement' => [
                    'start' => 79,
                    'notes' => 'Monitoring visit completed; guest house was cheaper than budgeted.',
                    'items' => [
                        ['Per diem – 4 days @ GHS 250', 1000.0, 'TRV-001', 'PD-0412'],
                        ['Fuel – Tamale, Yendi and Savelugu loop', 610.0, 'FUE-001', 'GOIL-88213'],
                        ['Guest house – 3 nights', 1040.0, 'TRV-001', 'GH-2291'],
                    ],
                    'flow' => [
                        ['submit'],
                        ['approve', 'abena.darko'],
                        ['approve', 'ama.mensah'],
                        ['approve', 'kwame.asante'],
                        ['settle', 'kofi.boateng', 'Refund of GHS 150.00 received in cash – receipt CR-0142.'],
                    ],
                ],
            ],
            [
                'key' => 'retired-top-up',
                'type' => 'advance',
                'requester' => 'esi.quaye',
                'notes' => 'Community health outreach – Ejura',
                'start' => 75,
                'items' => [
                    ['Venue and PA system hire', 1200.0, 'COM-001'],
                    ['Refreshments for 120 participants', 2400.0, 'COM-001'],
                    ['Volunteer transport', 1500.0, 'TRV-001'],
                    ['Facilitator allowances', 1400.0, 'COM-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['comment', 'esi.quaye', 'Attendance is expected to be higher than usual because it coincides with market day.'],
                    ['approve', 'abena.darko'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                    ['approve', 'efua.owusu', 'Approved – please keep the attendance sheets for the donor report.'],
                    ['disburse', 'serwaa.amponsah', 'PV-2026-0102'],
                ],
                'retirement' => [
                    'start' => 64,
                    'notes' => 'Turnout was 138 people, so refreshments and transport ran over budget.',
                    'items' => [
                        ['Venue and PA system hire', 1200.0, 'COM-001', 'EJ-0091'],
                        ['Refreshments for 138 participants', 2580.0, 'COM-001', 'EJ-0092'],
                        ['Volunteer transport', 1640.0, 'TRV-001', 'EJ-0093'],
                        ['Facilitator allowances', 1600.0, 'COM-001', 'EJ-0094'],
                    ],
                    'flow' => [
                        ['submit'],
                        ['approve', 'abena.darko', 'Overspend explained by the higher turnout.'],
                        ['approve', 'ama.mensah'],
                        ['approve', 'kwame.asante'],
                        ['settle', 'serwaa.amponsah', 'Top-up of GHS 520.00 paid to Esi Quaye in cash.'],
                    ],
                ],
            ],
            [
                'key' => 'retired-nil',
                'type' => 'advance',
                'requester' => 'nana.yeboah',
                'notes' => 'Driver allowances – Northern Region trip',
                'start' => 70,
                'items' => [
                    ['Night allowance – 4 nights', 800.0, 'TRV-001'],
                    ['Meals on the road', 400.0, 'TRV-001'],
                    ['Tolls and parking', 200.0, 'FUE-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'yaw.owusu'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0103'],
                ],
                'retirement' => [
                    'start' => 61,
                    'notes' => 'Spent exactly as advanced.',
                    'items' => [
                        ['Night allowance – 4 nights', 800.0, 'TRV-001', 'NA-1102'],
                        ['Meals on the road', 400.0, 'TRV-001', 'NA-1103'],
                        ['Tolls and parking', 200.0, 'FUE-001', 'NA-1104'],
                    ],
                    'flow' => [
                        ['submit'],
                        ['approve', 'yaw.owusu'],
                        ['approve', 'ama.mensah'],
                        ['settle', 'kofi.boateng', 'Nil balance – advance fully accounted for.'],
                    ],
                ],
            ],
            [
                'key' => 'expense-disbursed-supplies',
                'type' => 'expense',
                'requester' => 'adjoa.tetteh',
                'notes' => 'Office supplies for Takoradi office – Q3',
                'start' => 60,
                'items' => [
                    ['A4 paper – 10 reams', 450.0, 'OFS-001', 'TK-55120'],
                    ['Printer toner cartridge', 280.0, 'OFS-001', 'TK-55121'],
                    ['Assorted stationery', 50.0, 'OFS-001', 'TK-55122'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'ekow.hammond'],
                    ['approve', 'ama.mensah'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0104'],
                ],
            ],
            [
                'key' => 'expense-disbursed-vehicle',
                'type' => 'expense',
                'requester' => 'nana.yeboah',
                'notes' => 'Vehicle servicing GR-2214-21',
                'start' => 55,
                'items' => [
                    ['Engine oil and filters', 850.0, 'VEH-001', 'AUTO-7731'],
                    ['Front brake pads', 950.0, 'VEH-001', 'AUTO-7732'],
                    ['Workshop labour', 550.0, 'VEH-001', 'AUTO-7733'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'yaw.owusu', 'Service was due at 60,000 km.'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0105'],
                ],
            ],
            [
                'key' => 'retirement-in-workflow',
                'type' => 'advance',
                'requester' => 'afia.boakye',
                'notes' => 'Training of community volunteers – Ga East',
                'start' => 34,
                'items' => [
                    ['Hall hire – 2 days', 800.0, 'COM-001'],
                    ['Training materials and handouts', 900.0, 'COM-001'],
                    ['Lunch for 30 volunteers – 2 days', 1500.0, 'COM-001'],
                    ['Transport refunds', 1000.0, 'TRV-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'abena.darko'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0106'],
                ],
                'retirement' => [
                    'start' => 20,
                    'notes' => 'Two volunteers did not attend on day two.',
                    'items' => [
                        ['Hall hire – 2 days', 800.0, 'COM-001', 'GE-3301'],
                        ['Training materials and handouts', 870.0, 'COM-001', 'GE-3302'],
                        ['Lunch for 28 volunteers – 2 days', 1450.0, 'COM-001', 'GE-3303'],
                        ['Transport refunds', 980.0, 'TRV-001', 'GE-3304'],
                    ],
                    'flow' => [
                        ['submit'],
                        ['approve', 'abena.darko', 'Attendance register attached and checked.'],
                    ],
                ],
            ],
            [
                'key' => 'overdue-borehole',
                'type' => 'advance',
                'requester' => 'kwabena.ofori',
                'notes' => 'Borehole site assessment – Savelugu',
                'start' => 58,
                'items' => [
                    ['Hydrogeologist fee', 1800.0, 'COM-001'],
                    ['Fuel for site visits', 700.0, 'FUE-001'],
                    ['Per diem – 3 days', 600.0, 'TRV-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'abena.darko'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0107'],
                ],
            ],
            [
                'key' => 'overdue-nutrition-survey',
                'type' => 'advance',
                'requester' => 'esi.quaye',
                'notes' => 'Nutrition survey enumerator allowances – Offinso',
                'start' => 65,
                'items' => [
                    ['Enumerator allowances – 8 people', 6400.0, 'COM-001'],
                    ['Enumerator transport', 1500.0, 'TRV-001'],
                    ['Airtime and data bundles', 1000.0, 'COM-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'abena.darko'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                    ['approve', 'efua.owusu'],
                    ['disburse', 'serwaa.amponsah', 'PV-2026-0108'],
                ],
            ],
            [
                'key' => 'awaiting-retirement',
                'type' => 'advance',
                'requester' => 'yaa.agyeman',
                'notes' => 'Staff induction workshop – Accra',
                'start' => 10,
                'items' => [
                    ['Conference room hire', 1800.0, 'TRN-001'],
                    ['Meals for 25 new staff', 2200.0, 'TRN-001'],
                    ['Induction packs and printing', 1400.0, 'TRN-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'akua.addo'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                    ['approve', 'efua.owusu'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0109'],
                ],
            ],
            [
                'key' => 'approved-awaiting-disbursement-advance',
                'type' => 'advance',
                'requester' => 'fiifi.quansah',
                'notes' => 'Supplier verification trip to Kumasi',
                'start' => 5,
                'items' => [
                    ['Return bus fare – Accra to Kumasi', 650.0, 'PRC-002'],
                    ['Per diem – 2 days', 500.0, 'TRV-001'],
                    ['Hotel – 2 nights', 500.0, 'TRV-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'kojo.antwi'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                ],
            ],
            [
                'key' => 'approved-awaiting-disbursement-expense',
                'type' => 'expense',
                'requester' => 'afia.boakye',
                'notes' => 'Printing of IEC materials – malaria campaign',
                'start' => 4,
                'items' => [
                    ['A2 posters – 200 copies', 520.0, 'COM-001', 'PRT-2207'],
                    ['A5 flyers – 2,000 copies', 400.0, 'COM-001', 'PRT-2208'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'abena.darko'],
                    ['approve', 'ama.mensah'],
                ],
            ],
            [
                'key' => 'at-finance-director',
                'type' => 'advance',
                'requester' => 'kwabena.ofori',
                'notes' => 'Quarterly review meeting – Tamale',
                'start' => 6,
                'items' => [
                    ['Venue hire', 2500.0, 'COM-001'],
                    ['Meals for 60 participants', 4800.0, 'COM-001'],
                    ['Participant transport reimbursements', 3600.0, 'TRV-001'],
                    ['Stationery and printing', 1600.0, 'OFS-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'abena.darko'],
                    ['approve', 'ama.mensah', 'Budget line confirmed under the Northern programme grant.'],
                    ['approve', 'kwame.asante'],
                ],
            ],
            [
                'key' => 'at-finance-manager',
                'type' => 'advance',
                'requester' => 'nana.yeboah',
                'notes' => 'Fuel for field operations – November',
                'start' => 3,
                'items' => [
                    ['Diesel – 2 pickups for 2 weeks', 1400.0, 'FUE-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'yaw.owusu'],
                    ['approve', 'ama.mensah'],
                ],
            ],
            [
                'key' => 'at-finance-officer',
                'type' => 'expense',
                'requester' => 'adjoa.tetteh',
                'notes' => 'Repairs to office air conditioner',
                'start' => 3,
                'items' => [
                    ['Technician call-out and labour', 350.0, 'OFS-001', 'AC-0912'],
                    ['Gas refill', 300.0, 'OFS-001', 'AC-0913'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'ekow.hammond'],
                ],
            ],
            [
                'key' => 'at-head-of-department',
                'type' => 'advance',
                'requester' => 'esi.quaye',
                'notes' => 'Malaria prevention campaign – Ejisu',
                'start' => 1,
                'items' => [
                    ['Community durbar logistics', 1500.0, 'COM-001'],
                    ['Volunteer allowances', 1300.0, 'COM-001'],
                    ['Transport', 1000.0, 'TRV-001'],
                ],
                'flow' => [
                    ['submit'],
                ],
            ],
            [
                'key' => 'sent-back-request',
                'type' => 'advance',
                'requester' => 'fiifi.quansah',
                'notes' => 'Procurement committee meeting – refreshments and transport',
                'start' => 7,
                'items' => [
                    ['Refreshments and transport for committee members', 450.0, 'PRC-002'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'kojo.antwi'],
                    ['send_back', 'ama.mensah', 'Please split refreshments from transport and attach the attendance list.'],
                    ['comment', 'fiifi.quansah', 'Noted – I will update the lines and attach the list on Monday.'],
                ],
            ],
            [
                'key' => 'resubmitted-then-disbursed',
                'type' => 'expense',
                'requester' => 'yaa.agyeman',
                'notes' => 'Taxi fares for interview panel',
                'start' => 14,
                'items' => [
                    ['Taxi fares – panel members, day 1', 190.0, 'TRN-001', 'TX-4410'],
                    ['Taxi fares – panel members, day 2', 190.0, 'TRN-001', 'TX-4411'],
                ],
                'flow' => [
                    ['submit'],
                    ['send_back', 'akua.addo', 'Receipts for two of the trips are missing – please attach them before resubmitting.'],
                    ['resubmit'],
                    ['approve', 'akua.addo', 'Receipts are now complete.'],
                    ['approve', 'ama.mensah'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0110'],
                ],
            ],
            [
                'key' => 'rejected',
                'type' => 'advance',
                'requester' => 'kwabena.ofori',
                'notes' => 'Purchase of motorbike tyres',
                'start' => 21,
                'items' => [
                    ['Motorbike tyres – 4 bikes', 2200.0, 'VEH-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'abena.darko'],
                    ['approve', 'ama.mensah'],
                    ['reject', 'kwame.asante', 'Tyre purchases must go through Procurement with three quotations.'],
                ],
            ],
            [
                'key' => 'cancelled',
                'type' => 'advance',
                'requester' => 'afia.boakye',
                'notes' => 'Stakeholder engagement – Madina',
                'start' => 15,
                'items' => [
                    ['Hall hire and refreshments', 1200.0, 'COM-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['comment', 'afia.boakye', 'The municipal assembly has postponed the meeting to next quarter.'],
                    ['cancel'],
                ],
            ],
            [
                'key' => 'denied',
                'type' => 'expense',
                'requester' => 'nana.yeboah',
                'notes' => 'Car wash and minor repairs GT-5521-19',
                'start' => 25,
                'items' => [
                    ['Car wash – 4 visits', 120.0, 'VEH-001', 'CW-118'],
                    ['Wiper blades', 180.0, 'VEH-001', 'CW-119'],
                ],
                'flow' => [
                    ['submit'],
                    ['decline', 'yaw.owusu', 'Routine car washes and wiper blades are covered by the fleet maintenance contract.'],
                ],
            ],
            [
                'key' => 'draft-advance',
                'type' => 'advance',
                'requester' => 'esi.quaye',
                'notes' => 'Airtime for data collection tablets – Kumasi',
                'start' => 0,
                'items' => [
                    ['Data bundles – 12 tablets', 600.0, 'COM-001'],
                ],
                'flow' => [],
            ],
            [
                'key' => 'draft-expense',
                'type' => 'expense',
                'requester' => 'adjoa.tetteh',
                'notes' => 'Courier charges – Takoradi to Head Office',
                'start' => 0,
                'items' => [
                    ['Courier of signed contracts', 240.0, 'OFS-001', 'DHL-30021'],
                ],
                'flow' => [],
            ],
            [
                'key' => 'retirement-awaiting-settlement',
                'type' => 'advance',
                'requester' => 'adjoa.tetteh',
                'notes' => 'Transport for Takoradi office relocation',
                'start' => 32,
                'items' => [
                    ['Truck hire', 1800.0, 'OFS-001'],
                    ['Loading and offloading labour', 800.0, 'OFS-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'ekow.hammond'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0111'],
                ],
                'retirement' => [
                    'start' => 22,
                    'notes' => 'Labour cost less than quoted.',
                    'items' => [
                        ['Truck hire', 1800.0, 'OFS-001', 'TR-7781'],
                        ['Loading and offloading labour', 650.0, 'OFS-001', 'TR-7782'],
                    ],
                    'flow' => [
                        ['submit'],
                        ['approve', 'ekow.hammond'],
                        ['approve', 'ama.mensah'],
                        ['approve', 'kwame.asante', 'Refund of GHS 150.00 to be collected at settlement.'],
                    ],
                ],
            ],
            [
                'key' => 'retirement-draft',
                'type' => 'advance',
                'requester' => 'fiifi.quansah',
                'notes' => 'Market price survey – Makola and Kaneshie',
                'start' => 18,
                'items' => [
                    ['Transport between markets', 300.0, 'PRC-002'],
                    ['Per diem – 2 days', 500.0, 'TRV-001'],
                    ['Sample purchases', 100.0, 'PRC-002'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'kojo.antwi'],
                    ['approve', 'ama.mensah'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0112'],
                ],
                'retirement' => [
                    'start' => 2,
                    'notes' => 'Draft – still collecting the last receipt.',
                    'items' => [
                        ['Transport between markets', 280.0, 'PRC-002', 'MK-101'],
                        ['Per diem – 2 days', 500.0, 'TRV-001', 'MK-102'],
                        ['Sample purchases', 100.0, 'PRC-002', 'MK-103'],
                    ],
                    'flow' => [],
                ],
            ],
            [
                'key' => 'retirement-sent-back',
                'type' => 'advance',
                'requester' => 'nana.yeboah',
                'notes' => 'Northern trip fuel and tolls',
                'start' => 28,
                'items' => [
                    ['Diesel – Accra to Tamale and back', 1400.0, 'FUE-001'],
                    ['Road tolls', 200.0, 'FUE-001'],
                ],
                'flow' => [
                    ['submit'],
                    ['approve', 'yaw.owusu'],
                    ['approve', 'ama.mensah'],
                    ['approve', 'kwame.asante'],
                    ['disburse', 'kofi.boateng', 'PV-2026-0113'],
                ],
                'retirement' => [
                    'start' => 15,
                    'notes' => 'Fuel receipts from Kintampo and Buipe attached.',
                    'items' => [
                        ['Diesel – Accra to Tamale and back', 1420.0, 'FUE-001', 'FL-6610'],
                        ['Road tolls', 180.0, 'FUE-001', 'FL-6611'],
                    ],
                    'flow' => [
                        ['submit'],
                        ['send_back', 'yaw.owusu', 'The Buipe fuel receipt is not legible – please attach a clearer copy.'],
                    ],
                ],
            ],
        ];
    }
}
