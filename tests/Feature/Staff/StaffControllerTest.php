<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;

test('guest is redirected from index', function () {
    $response = $this->get(route('staff.index'));

    $response->assertRedirect(route('login'));
});
test('guest cannot create staff', function () {
    $response = $this->post(route('staff.store'), []);

    $response->assertRedirect(route('login'));
});
test('user without permission cannot access index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessStaff->value);

    $response = $this->actingAs($this->user)->get(route('staff.index'));

    $response->assertForbidden();
});
test('user without permission cannot create staff', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateStaff->value);

    $response = $this->actingAs($this->user)->post(route('staff.store'), []);

    $response->assertForbidden();
});
test('user without view descendant branches cannot view out of branch staff', function () {
    $this->role->revokePermissionTo(PermissionKey::ViewDescendantBranches->value);
    $otherBranch = Branch::factory()->create(['level_id' => $this->level->id]);
    $staff = Staff::factory()->withBranch($otherBranch)->create();

    $response = $this->actingAs($this->user)->get(route('staff.show', $staff));

    $response->assertForbidden();
});
test('user without view descendant branches cannot edit out of branch staff', function () {
    $this->role->revokePermissionTo(PermissionKey::ViewDescendantBranches->value);
    $otherBranch = Branch::factory()->create(['level_id' => $this->level->id]);
    $staff = Staff::factory()->withBranch($otherBranch)->create();

    $response = $this->actingAs($this->user)->get(route('staff.edit', $staff));

    $response->assertForbidden();
});
test('user without view descendant branches cannot update out of branch staff', function () {
    $this->role->revokePermissionTo(PermissionKey::ViewDescendantBranches->value);
    $otherBranch = Branch::factory()->create(['level_id' => $this->level->id]);
    $staff = Staff::factory()->withBranch($otherBranch)->create();

    $response = $this->actingAs($this->user)->put(route('staff.update', $staff), [
        'first_name' => 'Hacked',
        'last_name' => $staff->last_name,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
    ]);

    $response->assertForbidden();
    $this->assertDatabaseMissing('staff', ['id' => $staff->id, 'first_name' => 'Hacked']);
});
test('user without view descendant branches cannot delete out of branch staff', function () {
    $this->role->revokePermissionTo(PermissionKey::ViewDescendantBranches->value);
    $otherBranch = Branch::factory()->create(['level_id' => $this->level->id]);
    $staff = Staff::factory()->withBranch($otherBranch)->create();

    $response = $this->actingAs($this->user)->delete(route('staff.destroy', $staff));

    $response->assertForbidden();
    $this->assertDatabaseHas('staff', ['id' => $staff->id, 'deleted_at' => null]);
});
test('user can view staff in their own branch', function () {
    $this->role->revokePermissionTo(PermissionKey::ViewDescendantBranches->value);
    $staff = Staff::factory()->withBranch($this->branch)->create();

    $response = $this->actingAs($this->user)->get(route('staff.show', $staff));

    $response->assertOk();
});
test('cannot create user with existing email on staff create', function () {
    $existingUser = User::factory()->create();
    $staff = Staff::factory()->make();

    $response = $this->actingAs($this->user)->post(route('staff.store'), [
        'first_name' => $staff->first_name,
        'last_name' => $staff->last_name,
        'email' => $staff->email,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
        'user_action' => 'create',
        'user_email' => $existingUser->email,
        'user_password' => 'Password1!',
        'user_password_confirmation' => 'Password1!',
    ]);

    $response->assertSessionHasErrors('user_email');
});
test('cannot link already linked user to another staff on update', function () {
    $linkedUser = User::factory()->create();
    Staff::factory()->withUser($linkedUser)->create();

    $otherStaff = Staff::factory()->create();

    $response = $this->actingAs($this->user)->put(route('staff.update', $otherStaff), [
        'first_name' => $otherStaff->first_name,
        'last_name' => $otherStaff->last_name,
        'department_id' => $otherStaff->department_id,
        'position_id' => $otherStaff->position_id,
        'user_action' => 'link',
        'user_id' => $linkedUser->id,
    ]);

    $response->assertSessionHasErrors('user_id');
});
test('updating staff with existing linked user does not change the link', function () {
    $linkedUser = User::factory()->create();
    $staff = Staff::factory()->withUser($linkedUser)->create();

    $differentUser = User::factory()->create();

    $this->actingAs($this->user)->put(route('staff.update', $staff), [
        'first_name' => $staff->first_name,
        'last_name' => $staff->last_name,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
        'user_action' => 'link',
        'user_id' => $differentUser->id,
    ]);

    $this->assertDatabaseHas('staff', ['id' => $staff->id, 'user_id' => $linkedUser->id]);
});
test('authorized user can view staff index', function () {
    $response = $this->actingAs($this->user)->get(route('staff.index'));

    $response->assertOk();
});
test('authorized user can view create form', function () {
    $response = $this->actingAs($this->user)->get(route('staff.create'));

    $response->assertOk();
});
test('can create staff without user', function () {
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $response = $this->actingAs($this->user)->post(route('staff.store'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);

    $response->assertRedirect(route('staff.index'));
    $this->assertDatabaseHas('staff', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'department_id' => $department->id,
        'position_id' => $position->id,
    ]);
});
test('can update staff', function () {
    $staff = Staff::factory()->create();

    $response = $this->actingAs($this->user)->put(route('staff.update', $staff), [
        'first_name' => 'UpdatedFirst',
        'last_name' => $staff->last_name,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
    ]);

    $response->assertRedirect(route('staff.index'));
    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'first_name' => 'UpdatedFirst',
    ]);
});
test('store assembles phone country and phone number', function () {
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $this->actingAs($this->user)->post(route('staff.store'), [
        'first_name' => 'Phone',
        'last_name' => 'Create',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'phone_country' => 'GH',
        'phone_number' => '0246227810',
    ]);

    $this->assertDatabaseHas('staff', [
        'first_name' => 'Phone',
        'last_name' => 'Create',
        'phone' => '+233246227810',
    ]);
});
test('update assembles phone country and phone number', function () {
    $staff = Staff::factory()->create(['phone' => null]);

    $this->actingAs($this->user)->put(route('staff.update', $staff), [
        'first_name' => $staff->first_name,
        'last_name' => $staff->last_name,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
        'phone_country' => 'NG',
        'phone_number' => '08031234567',
    ]);

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'phone' => '+2348031234567',
    ]);
});
test('can delete staff', function () {
    $staff = Staff::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('staff.destroy', $staff));

    $response->assertRedirect(route('staff.index'));
    $this->assertDatabaseMissing('staff', ['id' => $staff->id, 'deleted_at' => null]);
});
test('authorized user can view edit form', function () {
    $staff = Staff::factory()->create();

    $response = $this->actingAs($this->user)->get(route('staff.edit', $staff));

    $response->assertOk();
});
test('user without permission cannot delete staff', function () {
    $this->role->revokePermissionTo(PermissionKey::DeleteStaff->value);
    $staff = Staff::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('staff.destroy', $staff));

    $response->assertForbidden();
});
test('can create staff with link user action', function () {
    $department = Department::factory()->create();
    $position = Position::factory()->create();
    $unlinkedUser = User::factory()->create();

    $response = $this->actingAs($this->user)->post(route('staff.store'), [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'user_action' => 'link',
        'user_id' => $unlinkedUser->id,
    ]);

    $response->assertRedirect(route('staff.index'));
    $this->assertDatabaseHas('staff', [
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'user_id' => $unlinkedUser->id,
    ]);
});
test('can update staff with link user action', function () {
    $staff = Staff::factory()->create(['user_id' => null]);
    $unlinkedUser = User::factory()->create();

    $response = $this->actingAs($this->user)->put(route('staff.update', $staff), [
        'first_name' => $staff->first_name,
        'last_name' => $staff->last_name,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
        'user_action' => 'link',
        'user_id' => $unlinkedUser->id,
    ]);

    $response->assertRedirect(route('staff.index'));
    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'user_id' => $unlinkedUser->id,
    ]);
});
test('edit form for staff without user includes unlinked users', function () {
    $staff = Staff::factory()->create(['user_id' => null]);

    $response = $this->actingAs($this->user)->get(route('staff.edit', $staff));

    $response->assertOk();
    $response->assertViewHas('unlinkedUsers');
});
test('edit form for staff with linked user has empty unlinked users', function () {
    $linkedUser = User::factory()->create();
    $staff = Staff::factory()->withUser($linkedUser)->create();

    $response = $this->actingAs($this->user)->get(route('staff.edit', $staff));

    $response->assertOk();
    $unlinkedUsers = $response->viewData('unlinkedUsers');
    expect($unlinkedUsers)->toHaveCount(0);
});
test('can create staff with create user action', function () {
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $response = $this->actingAs($this->user)->post(route('staff.store'), [
        'first_name' => 'Alice',
        'last_name' => 'Wonderland',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'user_action' => 'create',
        'user_email' => 'alice@example.com',
        'user_password' => 'Password1!',
        'user_password_confirmation' => 'Password1!',
    ]);

    $response->assertRedirect(route('staff.index'));
    $this->assertDatabaseHas('staff', ['first_name' => 'Alice', 'last_name' => 'Wonderland']);
    $this->assertDatabaseHas('users', ['email' => 'alice@example.com']);
});
test('store fails validation when user action is create but email missing', function () {
    $department = Department::factory()->create();
    $position = Position::factory()->create();

    $response = $this->actingAs($this->user)->post(route('staff.store'), [
        'first_name' => 'Bob',
        'last_name' => 'Builder',
        'department_id' => $department->id,
        'position_id' => $position->id,
        'user_action' => 'create',
        'user_password' => 'Password1!',
        'user_password_confirmation' => 'Password1!',
    ]);

    $response->assertSessionHasErrors('user_email');
});
test('update with create user action links new user to staff', function () {
    $staff = Staff::factory()->create(['user_id' => null]);

    $response = $this->actingAs($this->user)->put(route('staff.update', $staff), [
        'first_name' => $staff->first_name,
        'last_name' => $staff->last_name,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
        'user_action' => 'create',
        'user_email' => 'newuser@example.com',
        'user_password' => 'Password1!',
        'user_password_confirmation' => 'Password1!',
    ]);

    $response->assertRedirect(route('staff.index'));
    $this->assertDatabaseHas('users', ['email' => 'newuser@example.com']);
    $staff->refresh();
    expect($staff->user_id)->not->toBeNull();
});
test('update fails validation when user action is create but email missing', function () {
    $staff = Staff::factory()->create(['user_id' => null]);

    $response = $this->actingAs($this->user)->put(route('staff.update', $staff), [
        'first_name' => $staff->first_name,
        'last_name' => $staff->last_name,
        'department_id' => $staff->department_id,
        'position_id' => $staff->position_id,
        'user_action' => 'create',
        'user_password' => 'Password1!',
        'user_password_confirmation' => 'Password1!',
    ]);

    $response->assertSessionHasErrors('user_email');
});
