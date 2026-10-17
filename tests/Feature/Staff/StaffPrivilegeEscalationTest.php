<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function strongRole(): Role
{
    $role = Role::create(['name' => 'strong-' . Str::uuid(), 'guard_name' => 'web']);
    $role->givePermissionTo(Permission::findOrCreate(PermissionKey::AccessSettings->value, 'web'));
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    return $role;
}

function staffCreatePayload(array $extra = []): array
{
    $staff = Staff::factory()->make();

    return array_merge([
        'first_name' => $staff->first_name,
        'last_name' => $staff->last_name,
        'email' => $staff->email,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
        'branch_id' => test()->branch->id,
    ], $extra);
}

test('creating a login from the staff form needs the create user permission', function () {
    Notification::fake();
    $this->role->revokePermissionTo(PermissionKey::CreateUser->value);

    $response = $this->actingAs($this->user)->post(route('staff.store'), staffCreatePayload([
        'user_action' => 'create',
        'user_email' => 'new-login@example.com',
    ]));

    $response->assertForbidden();
    expect(User::query()->where('email', 'new-login@example.com')->exists())->toBeFalse();
});

test('staff form cannot assign a role carrying permissions the actor does not hold', function () {
    Notification::fake();
    $strong = strongRole();
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $response = $this->actingAs($this->user)->post(route('staff.store'), staffCreatePayload([
        'user_action' => 'create',
        'user_email' => 'sneaky-admin@example.com',
        'user_roles' => [$strong->id],
    ]));

    $response->assertForbidden();
    expect(User::query()->where('email', 'sneaky-admin@example.com')->exists())->toBeFalse();
});

test('staff update cannot create a login with an escalated role', function () {
    Notification::fake();
    $strong = strongRole();
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    $staff = Staff::factory()->withBranch($this->branch)->create();

    $response = $this->actingAs($this->user)->put(route('staff.update', $staff), [
        'first_name' => $staff->first_name,
        'last_name' => $staff->last_name,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
        'branch_id' => $this->branch->id,
        'user_action' => 'create',
        'user_email' => 'sneaky-update@example.com',
        'user_roles' => [$strong->id],
    ]);

    $response->assertForbidden();
    expect(User::query()->where('email', 'sneaky-update@example.com')->exists())->toBeFalse();
});

test('staff form cannot place staff in a branch outside the actor access', function () {
    Notification::fake();
    $this->role->revokePermissionTo(PermissionKey::ViewDescendantBranches->value);
    $otherBranch = Branch::factory()->create(['level_id' => $this->level->id]);

    $response = $this->actingAs($this->user)->post(route('staff.store'), staffCreatePayload(['branch_id' => $otherBranch->id, 'user_action' => 'none']));

    $response->assertForbidden();
});

test('a user cannot move their own staff record to another branch', function () {
    $otherBranch = Branch::factory()->create(['level_id' => $this->level->id]);
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

    $response = $this->actingAs($this->user)->put(route('staff.update', $staff), [
        'first_name' => $staff->first_name,
        'last_name' => $staff->last_name,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
        'branch_id' => $otherBranch->id,
        'user_action' => 'link',
        'user_id' => $this->user->id,
    ]);

    $response->assertForbidden();
    expect($staff->fresh()->branch_id)->toBe($this->branch->id);
});

test('editing the email or roles of a more privileged user is refused', function () {
    $strong = strongRole();
    $admin = User::factory()->create();
    $admin->assignRole($strong);
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $changeEmail = $this->actingAs($this->user)->put(route('users.update', $admin), [
        'first_name' => $admin->first_name, 'last_name' => $admin->last_name,
        'email' => 'attacker-controlled@example.com', 'roles' => [$strong->id],
    ]);
    $stripRoles = $this->actingAs($this->user)->put(route('users.update', $admin), [
        'first_name' => $admin->first_name, 'last_name' => $admin->last_name,
        'email' => $admin->email, 'roles' => [],
    ]);

    $changeEmail->assertForbidden();
    $stripRoles->assertForbidden();
    expect($admin->fresh()->email)->toBe($admin->email)->and($admin->fresh()->hasRole($strong))->toBeTrue();
});

test('a more privileged user can still have their name corrected', function () {
    $strong = strongRole();
    $admin = User::factory()->create();
    $admin->assignRole($strong);
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);
    app(Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

    $this->actingAs($this->user)->put(route('users.update', $admin), [
        'first_name' => 'Corrected', 'last_name' => $admin->last_name,
        'email' => $admin->email, 'roles' => [$strong->id],
    ])->assertRedirect(route('users.index'));

    expect($admin->fresh()->first_name)->toBe('Corrected');
});
