<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant\User;
use Illuminate\Support\Str;

test('guest is redirected from index', function () {
    $this->get(route('roles.index'))->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $this->get(route('roles.create'))->assertRedirect(route('login'));
});
test('guest is redirected from store', function () {
    $this->post(route('roles.store'), [])->assertRedirect(route('login'));
});
test('guest is redirected from permissions edit', function () {
    $role = Role::create(['name' => 'guest-test-' . Str::uuid(), 'guard_name' => 'web']);

    $this->get(route('roles.permissions.edit', $role))->assertRedirect(route('login'));
});
test('user without access permission cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessRoles->value);

    $this->actingAs($this->user)->get(route('roles.index'))->assertForbidden();
});
test('user without access permission cannot view edit', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessRoles->value);
    $role = Role::create(['name' => 'auth-test-' . Str::uuid(), 'guard_name' => 'web']);

    $this->actingAs($this->user)->get(route('roles.edit', $role))->assertForbidden();
});
test('user without manage role permissions cannot view or update permissions even with access roles', function () {
    $this->role->revokePermissionTo(PermissionKey::ManageRolePermissions->value);
    $role = Role::create(['name' => 'perm-test-' . Str::uuid(), 'guard_name' => 'web']);

    $this->actingAs($this->user)->get(route('roles.permissions.edit', $role))->assertForbidden();
    $this->actingAs($this->user)->put(route('roles.permissions.update', $role), ['permissions' => []])->assertForbidden();
});
test('user without access permission cannot view permissions edit', function () {
    $this->role->revokePermissionTo(PermissionKey::ManageRolePermissions->value);
    $role = Role::create(['name' => 'perm-test-' . Str::uuid(), 'guard_name' => 'web']);

    $this->actingAs($this->user)->get(route('roles.permissions.edit', $role))->assertForbidden();
});
test('user without create permission cannot view create', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateRole->value);

    $this->actingAs($this->user)->get(route('roles.create'))->assertForbidden();
});
test('user without create permission cannot store', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateRole->value);

    $this->actingAs($this->user)->post(route('roles.store'), ['name' => 'Manager'])->assertForbidden();
});
test('user without delete permission cannot destroy', function () {
    $this->role->revokePermissionTo(PermissionKey::DeleteRole->value);
    $role = Role::create(['name' => 'delete-test-' . Str::uuid(), 'guard_name' => 'web']);

    $this->actingAs($this->user)->delete(route('roles.destroy', $role))->assertForbidden();
});
test('authorised user can view index', function () {
    $this->actingAs($this->user)->get(route('roles.index'))->assertOk();
});
test('authorised user can view create form', function () {
    $this->actingAs($this->user)->get(route('roles.create'))->assertOk();
});
test('authorised user can store valid role', function () {
    $roleName = 'Manager-' . Str::uuid();

    $response = $this->actingAs($this->user)->post(route('roles.store'), [
        'name' => $roleName,
    ]);

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', ['name' => $roleName]);
});
test('storing a role logs activity', function () {
    $roleName = 'Manager-' . Str::uuid();

    $this->actingAs($this->user)->post(route('roles.store'), ['name' => $roleName]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'role',
        'event' => 'role.created',
    ]);
});
test('store fails validation when name is missing', function () {
    $response = $this->actingAs($this->user)->post(route('roles.store'), ['name' => '']);

    $response->assertSessionHasErrors('name');
});
test('authorised user can view edit form', function () {
    $role = Role::create(['name' => 'edit-form-' . Str::uuid(), 'guard_name' => 'web']);

    $response = $this->actingAs($this->user)->get(route('roles.edit', $role));

    $response->assertOk();
    $response->assertViewHas('role');
});
test('authorised user can update role name', function () {
    $role = Role::create(['name' => 'original-name-' . Str::uuid(), 'guard_name' => 'web']);
    $newName = 'updated-name-' . Str::uuid();

    $response = $this->actingAs($this->user)->put(route('roles.update', $role), [
        'name' => $newName,
    ]);

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $newName]);
});
test('updating a role logs activity', function () {
    $role = Role::create(['name' => 'update-log-' . Str::uuid(), 'guard_name' => 'web']);

    $this->actingAs($this->user)->put(route('roles.update', $role), ['name' => 'Renamed-' . Str::uuid()]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'role',
        'subject_id' => $role->id,
        'event' => 'role.updated',
    ]);
});
test('authorised user can destroy role with no users', function () {
    $role = Role::create(['name' => 'destroy-me-' . Str::uuid(), 'guard_name' => 'web']);

    $response = $this->actingAs($this->user)->delete(route('roles.destroy', $role));

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseMissing('roles', ['id' => $role->id]);
});
test('destroying a role logs activity', function () {
    $role = Role::create(['name' => 'destroy-log-' . Str::uuid(), 'guard_name' => 'web']);

    $this->actingAs($this->user)->delete(route('roles.destroy', $role));

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'role',
        'subject_id' => $role->id,
        'event' => 'role.deleted',
    ]);
});
test('destroy is blocked when role has users', function () {
    $role = Role::create(['name' => 'has-users-' . Str::uuid(), 'guard_name' => 'web']);
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($this->user)->delete(route('roles.destroy', $role));

    $response->assertRedirect(route('roles.index'));
    $this->assertDatabaseHas('roles', ['id' => $role->id]);
});
test('authorised user can view permissions edit form', function () {
    $role = Role::create(['name' => 'perm-edit-' . Str::uuid(), 'guard_name' => 'web']);

    $response = $this->actingAs($this->user)->get(route('roles.permissions.edit', $role));

    $response->assertOk();
    $response->assertViewHas('role');
    $response->assertViewHas('permissions');
});
test('permissions update syncs permissions to role', function () {
    $role = Role::create(['name' => 'sync-test-' . Str::uuid(), 'guard_name' => 'web']);
    $permission = Permission::findOrCreate(PermissionKey::AccessCostCodes->value, 'web');

    $response = $this->actingAs($this->user)->put(route('roles.permissions.update', $role), [
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect(route('roles.edit', $role));
    expect($role->fresh()->hasPermissionTo($permission->name))->toBeTrue();
});
test('permissions update with empty array removes all permissions', function () {
    $role = Role::create(['name' => 'remove-perms-' . Str::uuid(), 'guard_name' => 'web']);
    $permission = Permission::findOrCreate(PermissionKey::AccessCostCodes->value, 'web');
    $role->givePermissionTo($permission);

    $response = $this->actingAs($this->user)->put(route('roles.permissions.update', $role), [
        'permissions' => [],
    ]);

    $response->assertRedirect(route('roles.edit', $role));
    expect($role->fresh()->hasPermissionTo($permission->name))->toBeFalse();
});
test('permissions update logs activity', function () {
    $role = Role::create(['name' => 'sync-log-' . Str::uuid(), 'guard_name' => 'web']);
    $permission = Permission::findOrCreate(PermissionKey::AccessCostCodes->value, 'web');

    $this->actingAs($this->user)->put(route('roles.permissions.update', $role), [
        'permissions' => [$permission->id],
    ]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'role',
        'subject_id' => $role->id,
        'event' => 'role.permissions_synced',
    ]);
});
test('update permissions rejects permission actor lacks', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $role = Role::create(['name' => 'escalation-test-' . Str::uuid(), 'guard_name' => 'web']);
    $permission = Permission::findOrCreate(PermissionKey::AccessSettings->value, 'web');

    $response = $this->actingAs($this->user)->put(route('roles.permissions.update', $role), [
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect(route('roles.permissions.edit', $role));
    $response->assertSessionHas('error', __('flash.roles.permission_grant_denied', [
        'permissions' => PermissionKey::AccessSettings->value,
    ]));
    expect($role->fresh()->hasPermissionTo($permission))->toBeFalse();
});
test('update permissions allows resubmitting a permission the actor could not have granted', function () {
    $role = Role::create(['name' => 'resubmit-test-' . Str::uuid(), 'guard_name' => 'web']);
    $permission = Permission::findOrCreate(PermissionKey::AccessSettings->value, 'web');
    $role->givePermissionTo($permission);

    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    // Resubmitting the role's pre-existing permission (as the edit form
    // would, since it pre-checks the role's current grants) must not be
    // blocked even though the actor could never have granted it themselves.
    $response = $this->actingAs($this->user)->put(route('roles.permissions.update', $role), [
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect(route('roles.edit', $role));
    expect($role->fresh()->hasPermissionTo($permission))->toBeTrue();
});
test('update permissions allows removing a permission the actor could not have granted', function () {
    $role = Role::create(['name' => 'remove-escalated-' . Str::uuid(), 'guard_name' => 'web']);
    $permission = Permission::findOrCreate(PermissionKey::AccessSettings->value, 'web');
    $role->givePermissionTo($permission);

    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $response = $this->actingAs($this->user)->put(route('roles.permissions.update', $role), []);

    $response->assertRedirect(route('roles.edit', $role));
    expect($role->fresh()->hasPermissionTo($permission))->toBeFalse();
});
test('update permissions denial message truncates to first five with a remaining count', function () {
    $deniedPermissionValues = [
        PermissionKey::AccessSettings->value,
        PermissionKey::AccessCostCodes->value,
        PermissionKey::AccessDepartments->value,
        PermissionKey::AccessPositions->value,
        PermissionKey::AccessCurrencies->value,
        PermissionKey::AccessCashbook->value,
        PermissionKey::AccessCashCount->value,
    ];
    $permissionIds = [];
    foreach ($deniedPermissionValues as $value) {
        $permission = Permission::findOrCreate($value, 'web');
        $permissionIds[] = $permission->id;
        $this->role->revokePermissionTo($value);
    }
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $role = Role::create(['name' => 'truncate-test-' . Str::uuid(), 'guard_name' => 'web']);

    $response = $this->actingAs($this->user)->put(route('roles.permissions.update', $role), [
        'permissions' => $permissionIds,
    ]);

    $response->assertRedirect(route('roles.permissions.edit', $role));
    $response->assertSessionHas('error', fn($message) => str_contains($message, __('flash.and_more_count', ['count' => 2])));
    expect($role->fresh()->permissions)->toHaveCount(0);
});
test('guest is redirected from clone form', function () {
    $role = Role::create(['name' => 'guest-clone-' . Str::uuid(), 'guard_name' => 'web']);

    $this->get(route('roles.clone', $role))->assertRedirect(route('login'));
    $this->post(route('roles.clone.store', $role), ['name' => 'guest-clone-copy'])->assertRedirect(route('login'));
});
test('clone form prefills a copy name for the source role', function () {
    $role = Role::create(['name' => 'Approvers-' . Str::uuid(), 'guard_name' => 'web']);

    $response = $this->actingAs($this->user)->get(route('roles.clone', $role));

    $response->assertOk();
    $response->assertViewIs('tenant.roles.clone');
    $response->assertSee(__('roles.clone_default_name', ['name' => $role->name]));
});
test('clone copies every permission of the source role to the new role', function () {
    $source = Role::create(['name' => 'clone-source-' . Str::uuid(), 'guard_name' => 'web']);
    $source->givePermissionTo([PermissionKey::AccessCostCodes->value, PermissionKey::AccessDepartments->value]);
    $cloneName = 'clone-copy-' . Str::uuid();

    $response = $this->actingAs($this->user)->post(route('roles.clone.store', $source), [
        'name' => $cloneName,
    ]);

    $clone = Role::query()->where('name', $cloneName)->firstOrFail();
    $response->assertRedirect(route('roles.edit', $clone));
    $response->assertSessionHas('success', __('flash.roles.cloned', ['name' => $source->name]));

    expect($clone->guard_name)->toBe($source->guard_name);
    expect($clone->permissions->pluck('name')->sort()->values()->all())
        ->toBe($source->permissions->pluck('name')->sort()->values()->all());
    expect($source->fresh()->permissions)->toHaveCount(2);
});
test('cloning a role logs activity', function () {
    $source = Role::create(['name' => 'clone-log-source-' . Str::uuid(), 'guard_name' => 'web']);
    $cloneName = 'clone-log-copy-' . Str::uuid();

    $this->actingAs($this->user)->post(route('roles.clone.store', $source), ['name' => $cloneName]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'role',
        'subject_id' => Role::query()->where('name', $cloneName)->firstOrFail()->id,
        'event' => 'role.cloned',
    ]);
});
test('clone does not copy users of the source role', function () {
    $source = Role::create(['name' => 'clone-users-' . Str::uuid(), 'guard_name' => 'web']);
    User::factory()->create()->assignRole($source);
    $cloneName = 'clone-users-copy-' . Str::uuid();

    $this->actingAs($this->user)->post(route('roles.clone.store', $source), ['name' => $cloneName]);

    expect(Role::query()->where('name', $cloneName)->firstOrFail()->users()->count())->toBe(0);
    expect($source->users()->count())->toBe(1);
});
test('clone validates unique name', function () {
    $source = Role::create(['name' => 'clone-unique-' . Str::uuid(), 'guard_name' => 'web']);

    $response = $this->actingAs($this->user)->post(route('roles.clone.store', $source), [
        'name' => $source->name,
    ]);

    $response->assertSessionHasErrors('name');
});
test('clone validates required name', function () {
    $source = Role::create(['name' => 'clone-required-' . Str::uuid(), 'guard_name' => 'web']);

    $response = $this->actingAs($this->user)->post(route('roles.clone.store', $source), ['name' => '']);

    $response->assertSessionHasErrors('name');
});
test('clone is rejected when the source role holds permissions the actor lacks', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $source = Role::create(['name' => 'clone-escalation-' . Str::uuid(), 'guard_name' => 'web']);
    $source->givePermissionTo(PermissionKey::AccessSettings->value);
    $cloneName = 'clone-escalation-copy-' . Str::uuid();

    $response = $this->actingAs($this->user)->post(route('roles.clone.store', $source), [
        'name' => $cloneName,
    ]);

    $response->assertRedirect(route('roles.clone', $source));
    $response->assertSessionHas('error', __('flash.roles.permission_grant_denied', [
        'permissions' => PermissionKey::AccessSettings->value,
    ]));
    $response->assertSessionHasInput('name', $cloneName);
    $this->assertDatabaseMissing('roles', ['name' => $cloneName]);
});
test('clone requires the manage role permissions permission', function () {
    $this->role->revokePermissionTo(PermissionKey::ManageRolePermissions->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $source = Role::create(['name' => 'clone-no-manage-' . Str::uuid(), 'guard_name' => 'web']);
    $cloneName = 'clone-no-manage-copy-' . Str::uuid();

    $this->actingAs($this->user)->get(route('roles.clone', $source))->assertForbidden();
    $this->actingAs($this->user)->post(route('roles.clone.store', $source), ['name' => $cloneName])->assertForbidden();
    $this->assertDatabaseMissing('roles', ['name' => $cloneName]);
});
test('clone requires the create role permission', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateRole->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $source = Role::create(['name' => 'clone-no-create-' . Str::uuid(), 'guard_name' => 'web']);
    $cloneName = 'clone-no-create-copy-' . Str::uuid();

    $this->actingAs($this->user)->get(route('roles.clone', $source))->assertForbidden();
    $this->actingAs($this->user)->post(route('roles.clone.store', $source), ['name' => $cloneName])->assertForbidden();
    $this->assertDatabaseMissing('roles', ['name' => $cloneName]);
});
test('roles index shows clone action only to users who can clone', function () {
    $role = Role::create(['name' => 'clone-index-' . Str::uuid(), 'guard_name' => 'web']);

    $viewer = User::factory()->create();
    $viewer->givePermissionTo(PermissionKey::AccessRoles->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($viewer)->get(route('roles.index'))
        ->assertOk()
        ->assertDontSee(route('roles.clone', $role));

    $viewer->givePermissionTo(PermissionKey::CreateRole->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($viewer->fresh())->get(route('roles.index'))
        ->assertOk()
        ->assertDontSee(route('roles.clone', $role));

    $viewer->givePermissionTo(PermissionKey::ManageRolePermissions->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($viewer->fresh())->get(route('roles.index'))
        ->assertOk()
        ->assertSee(route('roles.clone', $role));
});
