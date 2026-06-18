<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\Tenant\PermissionKey;
use App\Models\Role;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Level;
use App\Models\Tenant\User;
use App\Services\BranchScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TenantAppTestCase;

class BranchScopeServiceTest extends TenantAppTestCase
{
    private BranchScopeService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(BranchScopeService::class);
    }

    // ── allowedBranchIds ──────────────────────────────────────────────────────

    public function test_returns_only_operational_branch_without_descendant_permission(): void
    {
        $restrictedRole = Role::create(['name' => 'restricted_' . Str::uuid(), 'guard_name' => 'web']);
        $user = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $user->assignRole($restrictedRole);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $result = $this->service->allowedBranchIds($user);

        $this->assertSame([$this->branch->id], $result);
    }

    public function test_returns_operational_branch_and_descendants_with_permission(): void
    {
        $level = Level::factory()->create(['name' => 'Sub Level', 'position' => 2]);
        $childBranch = Branch::factory()->create([
            'level_id' => $level->id,
            'position' => 1,
        ]);

        DB::connection('tenant')->table('branches_tree')->insert([
            'ancestor_id' => $this->branch->id,
            'descendant_id' => $childBranch->id,
            'depth' => 1,
        ]);

        $permissionKey = PermissionKey::ViewDescendantBranches->value;

        $viewDescendantPermission = \App\Models\Permission::where('name', $permissionKey)->first();
        if (! $viewDescendantPermission) {
            $viewDescendantPermission = \App\Models\Permission::create(['name' => $permissionKey, 'guard_name' => 'web']);
        }

        $this->role->givePermissionTo($viewDescendantPermission);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        $this->user->unsetRelation('roles');
        $this->user->unsetRelation('permissions');

        $result = $this->service->allowedBranchIds($this->user);

        $this->assertContains($this->branch->id, $result);
        $this->assertContains($childBranch->id, $result);
    }

    public function test_returns_single_element_array_when_no_descendants_exist(): void
    {
        $restrictedRole = Role::create(['name' => 'restricted2_' . Str::uuid(), 'guard_name' => 'web']);
        $user = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $user->assignRole($restrictedRole);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $result = $this->service->allowedBranchIds($user);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function test_result_contains_only_integers(): void
    {
        $result = $this->service->allowedBranchIds($this->user);

        foreach ($result as $id) {
            $this->assertIsInt($id);
        }
    }
}
