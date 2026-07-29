<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Department;

test('guest is redirected from index', function () {
    $this->get(route('cost-codes.index'))->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $this->get(route('cost-codes.create'))->assertRedirect(route('login'));
});
test('guest is redirected from store', function () {
    $this->post(route('cost-codes.store'), [])->assertRedirect(route('login'));
});
test('user without access permission cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessCostCodes->value);

    $this->actingAs($this->user)->get(route('cost-codes.index'))->assertForbidden();
});
test('user without access permission cannot view edit', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessCostCodes->value);
    $costCode = CostCode::factory()->create();

    $this->actingAs($this->user)->get(route('cost-codes.edit', $costCode))->assertForbidden();
});
test('user without create permission cannot view create', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateCostCode->value);

    $this->actingAs($this->user)->get(route('cost-codes.create'))->assertForbidden();
});
test('user without create permission cannot store', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateCostCode->value);
    $department = Department::factory()->create();

    $this->actingAs($this->user)->post(route('cost-codes.store'), [
        'name' => 'Test Code',
        'code' => 'TC-0001',
        'department_id' => $department->id,
    ])->assertForbidden();
});
test('user without delete permission cannot destroy', function () {
    $this->role->revokePermissionTo(PermissionKey::DeleteCostCode->value);
    $costCode = CostCode::factory()->create();

    $this->actingAs($this->user)->delete(route('cost-codes.destroy', $costCode))->assertForbidden();
});
test('authorised user can view index', function () {
    $this->actingAs($this->user)->get(route('cost-codes.index'))->assertOk();
});
test('authorised user can view create form with departments', function () {
    Department::factory()->create();

    $response = $this->actingAs($this->user)->get(route('cost-codes.create'));

    $response->assertOk();
    $response->assertViewHas('departments');
});
test('authorised user can store valid cost code', function () {
    $department = Department::factory()->create();

    $response = $this->actingAs($this->user)->post(route('cost-codes.store'), [
        'name' => 'Office Supplies',
        'code' => 'OS-1001',
        'department_id' => $department->id,
    ]);

    $response->assertRedirect(route('cost-codes.index'));
    $this->assertDatabaseHas('cost_codes', [
        'name' => 'Office Supplies',
        'code' => 'OS-1001',
        'department_id' => $department->id,
    ]);
});
test('store fails validation when name is missing', function () {
    $department = Department::factory()->create();

    $response = $this->actingAs($this->user)->post(route('cost-codes.store'), [
        'code' => 'OS-1001',
        'department_id' => $department->id,
    ]);

    $response->assertSessionHasErrors('name');
});
test('store fails validation when code is missing', function () {
    $department = Department::factory()->create();

    $response = $this->actingAs($this->user)->post(route('cost-codes.store'), [
        'name' => 'Office Supplies',
        'department_id' => $department->id,
    ]);

    $response->assertSessionHasErrors('code');
});
test('store fails validation when department id is missing', function () {
    $response = $this->actingAs($this->user)->post(route('cost-codes.store'), [
        'name' => 'Office Supplies',
        'code' => 'OS-1001',
    ]);

    $response->assertSessionHasErrors('department_id');
});
test('store fails validation when department id does not exist', function () {
    $response = $this->actingAs($this->user)->post(route('cost-codes.store'), [
        'name' => 'Office Supplies',
        'code' => 'OS-1001',
        'department_id' => 99999,
    ]);

    $response->assertSessionHasErrors('department_id');
});
test('authorised user can view edit form', function () {
    $costCode = CostCode::factory()->create();

    $response = $this->actingAs($this->user)->get(route('cost-codes.edit', $costCode));

    $response->assertOk();
    $response->assertViewHas('costCode');
    $response->assertViewHas('departments');
});
test('authorised user can update cost code', function () {
    $costCode = CostCode::factory()->create();
    $department = Department::factory()->create();

    $response = $this->actingAs($this->user)->put(route('cost-codes.update', $costCode), [
        'name' => 'Updated Name',
        'code' => 'UP-9999',
        'department_id' => $department->id,
    ]);

    $response->assertRedirect(route('cost-codes.index'));
    $this->assertDatabaseHas('cost_codes', [
        'id' => $costCode->id,
        'name' => 'Updated Name',
        'code' => 'UP-9999',
        'department_id' => $department->id,
    ]);
});
test('authorised user can destroy cost code', function () {
    $costCode = CostCode::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('cost-codes.destroy', $costCode));

    $response->assertRedirect(route('cost-codes.index'));
    $this->assertSoftDeleted('cost_codes', ['id' => $costCode->id]);
});
