<?php

declare(strict_types=1);

namespace Tests;

use App\Enums\FeatureFlag;
use App\Helpers\ResolvesAutomatedTestTenantMarker;
use App\Interfaces\FeatureFlagServiceInterface;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Level;
use App\Models\Tenant\User;
use Exception;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Override;
use PHPUnit\Framework\MockObject\MockObject;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

abstract class TenantAppTestCase extends BaseTestCase
{
    use DatabaseTransactions;
    use ResolvesAutomatedTestTenantMarker;

    private bool $tenantTransactionStarted = false;

    // Public so Pest's file-local helper functions (which run outside any class scope) can
    // read these fixtures via test()->tenant, test()->user, etc.
    public Tenant $tenant;

    public User $user;

    public Role $role;

    public Level $level;

    public Branch $branch;

    /** @var MockObject&FeatureFlagServiceInterface */
    public MockObject $featureFlagMock;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeTenancy();

        $this->withoutVite();

        $this->withoutMiddleware([
            InitializeTenancyByDomain::class,
            PreventAccessFromCentralDomains::class,
        ]);

        // DatabaseTransactions wraps the landlord connection only. Start a
        // separate transaction on the tenant connection so each test rolls
        // back tenant DB writes cleanly.
        $this->beginTenantDatabaseTransaction();

        // Bind a mock FeatureFlagServiceInterface that returns true for all
        // feature flags by default. This prevents Pennant DB writes (and
        // potential deadlocks) during parallel test runs. Override individual
        // flags inside a test with: $this->bindFeatureFlagMock(['flag' => false]);
        $this->bindFeatureFlagMock();

        // Unique permission cache key per process and tenant to avoid
        // collisions when tests run in parallel.
        $permissionRegistrar = app(\Spatie\Permission\PermissionRegistrar::class);
        $permissionRegistrar->cacheKey = 'spatie.permission.cache.test.' . getmypid() . '.' . $this->tenant->getTenantKey(); // @phpstan-ignore-line

        $this->init();
    }

    protected function initializeTenancy(): void
    {
        // Resolve the tenant via this worker's marker file (marker() folds in
        // TEST_TOKEN, so each parallel worker has its own marker/tenant and
        // never races another worker on the same tenant database) rather than
        // "most recently created" — otherwise any tenant created elsewhere
        // during the run becomes the "latest" tenant and hijacks every other
        // worker's tenancy for as long as it exists.
        //
        // The tenant itself must already exist by the time this runs: this
        // method executes after parent::setUp() has opened this test's
        // DatabaseTransactions transaction, and Postgres refuses to run
        // CREATE DATABASE inside a transaction block. So tenants can't be
        // lazily created here — the test runner scripts provision one tenant
        // per worker up front (see tests/run-tests-parallel.sh) before Pest
        // spawns any worker process.
        $markerFile = $this->markerFilePath($this->marker());
        $tenant = File::exists($markerFile)
            ? Tenant::find(File::get($markerFile))
            : Tenant::orderBy('created_at', 'desc')->first();

        if (! $tenant) {
            throw new Exception('No tenant found. Ensure a tenant exists before running tests.');
        }

        $this->tenant = $tenant;
        tenancy()->initialize($this->tenant);

        // Route requests through the tenant's own domain rather than the
        // central app URL. Central domains host landlord routes (e.g. the
        // marketing "/" route), which would otherwise collide with
        // domain-agnostic tenant routes such as "dashboard".
        $domain = $this->tenant->domains()->first()?->domain;
        if ($domain) {
            URL::forceRootUrl('http://' . $domain);
        }
    }

    protected function init(): void
    {
        $tenantKey = $this->tenant->getTenantKey();

        $this->level = Cache::store('array')->rememberForever(
            "test.{$tenantKey}.level",
            function () {
                $level = Level::first();
                if (! $level) {
                    $level = Level::factory()->create(['name' => 'Head Office', 'position' => 1]);
                }

                return $level;
            },
        );

        $this->branch = Cache::store('array')->rememberForever(
            "test.{$tenantKey}.branch",
            function () use ($tenantKey) {
                $branch = Branch::first();
                if (! $branch) {
                    $level = Cache::store('array')->get("test.{$tenantKey}.level");
                    $branch = Branch::factory()->create([
                        'name' => 'Main Branch',
                        'level_id' => $level->id,
                        'position' => 0,
                    ]);
                }

                return $branch;
            },
        );

        if ($this->branch->currency_id === null) {
            $this->branch->update(['currency_id' => Currency::factory()->create()->id]);
        }

        $permissions = Cache::store('array')->rememberForever(
            "test.{$tenantKey}.permissions",
            fn() => Permission::all(),
        );

        /** @var User $user */
        $user = User::factory()->create([
            'email' => Str::uuid7()->toString() . '@example.com',
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $this->user = $user;

        $this->role = Role::create(['name' => 'test_admin_' . Str::uuid()->toString(), 'guard_name' => 'web']);
        $this->role->givePermissionTo($permissions);
        $this->user->assignRole($this->role);

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');
    }

    /**
     * Bind a mock FeatureFlagServiceInterface into the container.
     * All feature flags default to true. Pass overrides to disable specific flags.
     *
     * @param array<string, bool> $overrides
     *
     * @return MockObject&FeatureFlagServiceInterface
     */
    protected function bindFeatureFlagMock(array $overrides = []): MockObject
    {
        /** @var MockObject&FeatureFlagServiceInterface $mock */
        $mock = $this->createMock(FeatureFlagServiceInterface::class);

        $flags = [];
        foreach (FeatureFlag::cases() as $flag) {
            $flags[$flag->value] = $overrides[$flag->value] ?? true;
        }

        $mock->method('getValue')->willReturnCallback(
            static function (string $featureClass) use ($flags): bool {
                foreach (FeatureFlag::cases() as $flag) {
                    if ($flag->value === $featureClass) {
                        return $flags[$flag->value] ?? true;
                    }
                }

                return true;
            },
        );

        $this->app->instance(FeatureFlagServiceInterface::class, $mock);
        $this->featureFlagMock = $mock;

        return $mock;
    }

    #[Override]
    protected function tearDown(): void
    {
        $this->rollbackTenantDatabaseTransaction();

        tenancy()->end();
        unset(
            $this->user,
            $this->role,
            $this->tenant,
        );

        parent::tearDown();
    }

    private function beginTenantDatabaseTransaction(): void
    {
        DB::connection('tenant')->beginTransaction();
        $this->tenantTransactionStarted = true;
    }

    private function rollbackTenantDatabaseTransaction(): void
    {
        if (! $this->tenantTransactionStarted) {
            return;
        }

        $connection = DB::connection('tenant');
        while ($connection->transactionLevel() > 0) {
            $connection->rollBack();
        }

        $this->tenantTransactionStarted = false;
    }
}
