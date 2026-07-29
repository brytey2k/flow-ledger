<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Role;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Level;
use App\Models\Tenant\User;
use App\Services\BranchScopeService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->service = app(BranchScopeService::class);
});
test('returns only operational branch without descendant permission', function () {
    $restrictedRole = Role::create(['name' => 'restricted_' . Str::uuid(), 'guard_name' => 'web']);
    $user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $user->assignRole($restrictedRole);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $result = $this->service->allowedBranchIds($user);

    expect($result)->toBe([$this->branch->id]);
});
test('returns operational branch and descendants with permission', function () {
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

    $viewDescendantPermission = App\Models\Permission::where('name', $permissionKey)->first();
    if (! $viewDescendantPermission) {
        $viewDescendantPermission = App\Models\Permission::create(['name' => $permissionKey, 'guard_name' => 'web']);
    }

    $this->role->givePermissionTo($viewDescendantPermission);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $this->user->unsetRelation('roles');
    $this->user->unsetRelation('permissions');

    $result = $this->service->allowedBranchIds($this->user);

    expect($result)->toContain($this->branch->id);
    expect($result)->toContain($childBranch->id);
});
test('returns single element array when no descendants exist', function () {
    $restrictedRole = Role::create(['name' => 'restricted2_' . Str::uuid(), 'guard_name' => 'web']);
    $user = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $user->assignRole($restrictedRole);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $result = $this->service->allowedBranchIds($user);

    expect($result)->toBeArray();
    expect($result)->toHaveCount(1);
});
test('result contains only integers', function () {
    $result = $this->service->allowedBranchIds($this->user);

    foreach ($result as $id) {
        expect($id)->toBeInt();
    }
});
