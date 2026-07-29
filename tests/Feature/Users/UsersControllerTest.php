<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant\User;
use Illuminate\Support\Str;

test('guest is redirected from index', function () {
    $this->get(route('users.index'))->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $this->get(route('users.create'))->assertRedirect(route('login'));
});
test('guest is redirected from store', function () {
    $this->post(route('users.store'), [])->assertRedirect(route('login'));
});
test('guest is redirected from permissions edit', function () {
    $user = User::factory()->create();

    $this->get(route('users.permissions.edit', $user))->assertRedirect(route('login'));
});
test('user without access permission cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessUsers->value);

    $this->actingAs($this->user)->get(route('users.index'))->assertForbidden();
});
test('user without access permission cannot view edit', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessUsers->value);
    $user = User::factory()->create();

    $this->actingAs($this->user)->get(route('users.edit', $user))->assertForbidden();
});
test('user without manage permissions permission cannot view permissions edit', function () {
    $this->role->revokePermissionTo(PermissionKey::ManageUserPermissions->value);
    $user = User::factory()->create();

    $this->actingAs($this->user)->get(route('users.permissions.edit', $user))->assertForbidden();
});
test('user without manage permissions permission cannot update permissions', function () {
    $this->role->revokePermissionTo(PermissionKey::ManageUserPermissions->value);
    $user = User::factory()->create();
    $permission = Permission::findOrCreate(PermissionKey::AccessCostCodes->value, 'web');

    $this->actingAs($this->user)->put(route('users.permissions.update', $user), [
        'permissions' => [$permission->id],
    ])->assertForbidden();
});
test('access users permission alone does not grant permissions edit', function () {
    $this->role->revokePermissionTo(PermissionKey::ManageUserPermissions->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $user = User::factory()->create();

    // Holding only the read-level "access users" permission (e.g. a user
    // who can view the user list) must not be enough to reach the
    // permissions-management endpoints.
    $this->actingAs($this->user)->get(route('users.permissions.edit', $user))->assertForbidden();
    $this->actingAs($this->user)->put(route('users.permissions.update', $user), [
        'permissions' => [],
    ])->assertForbidden();
});
test('user without create permission cannot view create', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateUser->value);

    $this->actingAs($this->user)->get(route('users.create'))->assertForbidden();
});
test('user without create permission cannot store', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateUser->value);

    $this->actingAs($this->user)->post(route('users.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertForbidden();
});
test('user without delete permission cannot destroy', function () {
    $this->role->revokePermissionTo(PermissionKey::DeleteUser->value);
    $user = User::factory()->create();

    $this->actingAs($this->user)->delete(route('users.destroy', $user))->assertForbidden();
});
test('authorised user can view index', function () {
    $this->actingAs($this->user)->get(route('users.index'))->assertOk();
});
test('authorised user can view create form with roles', function () {
    $response = $this->actingAs($this->user)->get(route('users.create'));

    $response->assertOk();
    $response->assertViewHas('roles');
});
test('authorised user can store valid user', function () {
    $email = 'newuser-' . Str::uuid() . '@example.com';

    $response = $this->actingAs($this->user)->post(route('users.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'branch_id' => $this->branch->id,
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => $email,
    ]);
});
test('store fails validation for duplicate email', function () {
    $existing = User::factory()->create();

    $response = $this->actingAs($this->user)->post(route('users.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Doe',
        'email' => $existing->email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertSessionHasErrors('email');
});
test('store fails validation when passwords do not match', function () {
    $response = $this->actingAs($this->user)->post(route('users.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'mismatch-' . Str::uuid() . '@example.com',
        'password' => 'password123',
        'password_confirmation' => 'different456',
    ]);

    $response->assertSessionHasErrors('password');
});
test('authorised user can view edit form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->user)->get(route('users.edit', $user));

    $response->assertOk();
    $response->assertViewHas('user');
    $response->assertViewHas('roles');
});
test('edit view hides manage permissions link without permission', function () {
    $this->role->revokePermissionTo(PermissionKey::ManageUserPermissions->value);
    $user = User::factory()->create();

    $response = $this->actingAs($this->user)->get(route('users.edit', $user));

    $response->assertOk();
    $response->assertDontSee(__('users.buttons.manage_perms'));
});
test('edit view shows manage permissions link with permission', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->user)->get(route('users.edit', $user));

    $response->assertOk();
    $response->assertSee(__('users.buttons.manage_perms'));
});
test('authorised user can update user name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->user)->put(route('users.update', $user), [
        'first_name' => 'Updated',
        'last_name' => 'Name',
        'email' => $user->email,
        'branch_id' => $this->branch->id,
    ]);

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => 'Updated',
        'last_name' => 'Name',
    ]);
});
test('authorised user can destroy user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('users.destroy', $user));

    $response->assertRedirect(route('users.index'));
    $this->assertDatabaseMissing('users', ['id' => $user->id, 'deleted_at' => null]);
});
test('authorised user can view permissions edit form', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($this->user)->get(route('users.permissions.edit', $user));

    $response->assertOk();
    $response->assertViewHas('user');
    $response->assertViewHas('permissions');
});
test('permissions update syncs permissions to user', function () {
    $user = User::factory()->create();
    $permission = Permission::findOrCreate(PermissionKey::AccessCostCodes->value, 'web');

    $response = $this->actingAs($this->user)->put(route('users.permissions.update', $user), [
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect(route('users.edit', $user));
    expect($user->fresh()->hasPermissionTo($permission->name))->toBeTrue();
});
test('permissions update with empty array removes all permissions', function () {
    $user = User::factory()->create();
    $permission = Permission::findOrCreate(PermissionKey::AccessCostCodes->value, 'web');
    $user->givePermissionTo($permission);

    $response = $this->actingAs($this->user)->put(route('users.permissions.update', $user), [
        'permissions' => [],
    ]);

    $response->assertRedirect(route('users.edit', $user));
    expect($user->fresh()->hasPermissionTo($permission->name))->toBeFalse();
});
test('permissions update logs activity', function () {
    $user = User::factory()->create();
    $permission = Permission::findOrCreate(PermissionKey::AccessCostCodes->value, 'web');

    $this->actingAs($this->user)->put(route('users.permissions.update', $user), [
        'permissions' => [$permission->id],
    ]);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'user',
        'subject_id' => $user->id,
        'event' => 'user.permissions_synced',
    ]);
});
test('store rejects role that grants permission actor lacks', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $adminRole = Role::create(['name' => 'escalation-role-' . Str::uuid(), 'guard_name' => 'web']);
    $adminRole->givePermissionTo(PermissionKey::AccessSettings->value);

    $email = 'escalate-store-' . Str::uuid() . '@example.com';

    $response = $this->actingAs($this->user)->post(route('users.store'), [
        'first_name' => 'Escalate',
        'last_name' => 'Attempt',
        'email' => $email,
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'branch_id' => $this->branch->id,
        'roles' => [$adminRole->id],
    ]);

    $response->assertRedirect(route('users.create'));
    $response->assertSessionHas('error', __('flash.users.permission_grant_denied', [
        'permissions' => PermissionKey::AccessSettings->value,
    ]));
    $this->assertDatabaseMissing('users', ['email' => $email]);
});
test('update rejects role that grants permission actor lacks', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $adminRole = Role::create(['name' => 'escalation-role-' . Str::uuid(), 'guard_name' => 'web']);
    $adminRole->givePermissionTo(PermissionKey::AccessSettings->value);

    $response = $this->actingAs($this->user)->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'roles' => [$adminRole->id],
    ]);

    $response->assertRedirect(route('users.edit', $user));
    $response->assertSessionHas('error', __('flash.users.permission_grant_denied', [
        'permissions' => PermissionKey::AccessSettings->value,
    ]));
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    expect($user->fresh()->hasRole($adminRole))->toBeFalse();
});
test('update allows resubmitting a role the actor could not have granted', function () {
    $user = User::factory()->create();
    $preExistingRole = Role::create(['name' => 'pre-existing-role-' . Str::uuid(), 'guard_name' => 'web']);
    $preExistingRole->givePermissionTo(PermissionKey::AccessSettings->value);
    $user->assignRole($preExistingRole);

    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    // Resubmitting the target's pre-existing role (as the edit form would,
    // since it pre-checks the target's current roles) must not be blocked
    // even though the actor could never have granted AccessSettings.
    $response = $this->actingAs($this->user)->put(route('users.update', $user), [
        'first_name' => $user->first_name,
        'last_name' => $user->last_name,
        'email' => $user->email,
        'roles' => [$preExistingRole->id],
    ]);

    $response->assertRedirect(route('users.index'));
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    expect($user->fresh()->hasRole($preExistingRole))->toBeTrue();
});
test('update permissions rejects permission actor lacks', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $user = User::factory()->create();
    $permission = Permission::findOrCreate(PermissionKey::AccessSettings->value, 'web');

    $response = $this->actingAs($this->user)->put(route('users.permissions.update', $user), [
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect(route('users.permissions.edit', $user));
    $response->assertSessionHas('error', __('flash.users.permission_grant_denied', [
        'permissions' => PermissionKey::AccessSettings->value,
    ]));
    expect($user->fresh()->hasPermissionTo($permission))->toBeFalse();
});
test('update permissions allows resubmitting a permission the actor could not have granted', function () {
    $user = User::factory()->create();
    $permission = Permission::findOrCreate(PermissionKey::AccessSettings->value, 'web');
    $user->givePermissionTo($permission);

    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $response = $this->actingAs($this->user)->put(route('users.permissions.update', $user), [
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect(route('users.edit', $user));
    expect($user->fresh()->hasPermissionTo($permission))->toBeTrue();
});
test('update permissions allows removing a permission the actor could not have granted', function () {
    $user = User::factory()->create();
    $permission = Permission::findOrCreate(PermissionKey::AccessSettings->value, 'web');
    $user->givePermissionTo($permission);

    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $response = $this->actingAs($this->user)->put(route('users.permissions.update', $user), []);

    $response->assertRedirect(route('users.edit', $user));
    expect($user->fresh()->hasPermissionTo($permission))->toBeFalse();
});
