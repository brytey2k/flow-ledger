<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;

test('guest is redirected from index', function () {
    $response = $this->get(route('positions.index'));

    $response->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $response = $this->get(route('positions.create'));

    $response->assertRedirect(route('login'));
});
test('guest cannot post to store', function () {
    $response = $this->post(route('positions.store'), ['name' => 'Manager']);

    $response->assertRedirect(route('login'));
});
test('user without access permission cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessPositions->value);

    $response = $this->actingAs($this->user)->get(route('positions.index'));

    $response->assertForbidden();
});
test('user without create permission cannot access create form', function () {
    $this->role->revokePermissionTo(PermissionKey::CreatePosition->value);

    $response = $this->actingAs($this->user)->get(route('positions.create'));

    $response->assertForbidden();
});
test('authorised user can view index', function () {
    $response = $this->actingAs($this->user)->get(route('positions.index'));

    $response->assertOk();
});
test('authorised user can view create form', function () {
    $response = $this->actingAs($this->user)->get(route('positions.create'));

    $response->assertOk();
});
test('authorised user can store position', function () {
    $response = $this->actingAs($this->user)->post(route('positions.store'), [
        'name' => 'Manager',
    ]);

    $response->assertRedirect(route('positions.index'));
    $this->assertDatabaseHas('positions', ['name' => 'Manager']);
});
test('store fails validation for missing name', function () {
    $response = $this->actingAs($this->user)->post(route('positions.store'), []);

    $response->assertSessionHasErrors('name');
});
test('authorised user can view edit form', function () {
    $position = Position::factory()->create();

    $response = $this->actingAs($this->user)->get(route('positions.edit', $position));

    $response->assertOk();
});
test('authorised user can update position', function () {
    $position = Position::factory()->create(['name' => 'Old Title']);

    $response = $this->actingAs($this->user)->put(route('positions.update', $position), [
        'name' => 'New Title',
    ]);

    $response->assertRedirect(route('positions.index'));
    $this->assertDatabaseHas('positions', ['id' => $position->id, 'name' => 'New Title']);
});
test('authorised user can delete position', function () {
    $position = Position::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('positions.destroy', $position));

    $response->assertRedirect(route('positions.index'));
    $this->assertSoftDeleted('positions', ['id' => $position->id]);
});
test('delete is blocked when position has staff', function () {
    $position = Position::factory()->create();
    $department = Department::factory()->create();
    Staff::factory()->create(['position_id' => $position->id, 'department_id' => $department->id]);

    $response = $this->actingAs($this->user)->delete(route('positions.destroy', $position));

    $response->assertRedirect();
    $this->assertDatabaseHas('positions', ['id' => $position->id]);
});
