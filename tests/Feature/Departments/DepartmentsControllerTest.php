<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Department;

test('guest is redirected from index', function () {
    $response = $this->get(route('departments.index'));

    $response->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $response = $this->get(route('departments.create'));

    $response->assertRedirect(route('login'));
});
test('guest cannot post to store', function () {
    $response = $this->post(route('departments.store'), ['name' => 'Finance']);

    $response->assertRedirect(route('login'));
});
test('user without access permission cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessDepartments->value);

    $response = $this->actingAs($this->user)->get(route('departments.index'));

    $response->assertForbidden();
});
test('user without create permission cannot access create form', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateDepartment->value);

    $response = $this->actingAs($this->user)->get(route('departments.create'));

    $response->assertForbidden();
});
test('authorised user can view index', function () {
    $response = $this->actingAs($this->user)->get(route('departments.index'));

    $response->assertOk();
});
test('authorised user can view create form', function () {
    $response = $this->actingAs($this->user)->get(route('departments.create'));

    $response->assertOk();
});
test('authorised user can store department', function () {
    $response = $this->actingAs($this->user)->post(route('departments.store'), [
        'name' => 'Finance',
    ]);

    $response->assertRedirect(route('departments.index'));
    $this->assertDatabaseHas('departments', ['name' => 'Finance']);
});
test('store fails validation for missing name', function () {
    $response = $this->actingAs($this->user)->post(route('departments.store'), []);

    $response->assertSessionHasErrors('name');
});
test('authorised user can view edit form', function () {
    $department = Department::factory()->create();

    $response = $this->actingAs($this->user)->get(route('departments.edit', $department));

    $response->assertOk();
});
test('authorised user can update department', function () {
    $department = Department::factory()->create(['name' => 'Old Name']);

    $response = $this->actingAs($this->user)->put(route('departments.update', $department), [
        'name' => 'New Name',
    ]);

    $response->assertRedirect(route('departments.index'));
    $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'New Name']);
});
test('authorised user can delete department', function () {
    $department = Department::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('departments.destroy', $department));

    $response->assertRedirect(route('departments.index'));
    $this->assertSoftDeleted('departments', ['id' => $department->id]);
});
