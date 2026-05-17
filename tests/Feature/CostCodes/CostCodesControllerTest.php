<?php

declare(strict_types=1);

namespace Tests\Feature\CostCodes;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Department;
use Tests\TenantAppTestCase;

class CostCodesControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('cost-codes.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('cost-codes.create'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_store(): void
    {
        $this->post(route('cost-codes.store'), [])->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessCostCodes->value);

        $this->actingAs($this->user)->get(route('cost-codes.index'))->assertForbidden();
    }

    public function test_user_without_access_permission_cannot_view_edit(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessCostCodes->value);
        $costCode = CostCode::factory()->create();

        $this->actingAs($this->user)->get(route('cost-codes.edit', $costCode))->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_view_create(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCostCode->value);

        $this->actingAs($this->user)->get(route('cost-codes.create'))->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_store(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCostCode->value);
        $department = Department::factory()->create();

        $this->actingAs($this->user)->post(route('cost-codes.store'), [
            'name' => 'Test Code',
            'code' => 'TC-0001',
            'department_id' => $department->id,
        ])->assertForbidden();
    }

    public function test_user_without_delete_permission_cannot_destroy(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DeleteCostCode->value);
        $costCode = CostCode::factory()->create();

        $this->actingAs($this->user)->delete(route('cost-codes.destroy', $costCode))->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_index(): void
    {
        $this->actingAs($this->user)->get(route('cost-codes.index'))->assertOk();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_create_form_with_departments(): void
    {
        Department::factory()->create();

        $response = $this->actingAs($this->user)->get(route('cost-codes.create'));

        $response->assertOk();
        $response->assertViewHas('departments');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_store_valid_cost_code(): void
    {
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
    }

    public function test_store_fails_validation_when_name_is_missing(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->user)->post(route('cost-codes.store'), [
            'code' => 'OS-1001',
            'department_id' => $department->id,
        ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_store_fails_validation_when_code_is_missing(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->user)->post(route('cost-codes.store'), [
            'name' => 'Office Supplies',
            'department_id' => $department->id,
        ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_store_fails_validation_when_department_id_is_missing(): void
    {
        $response = $this->actingAs($this->user)->post(route('cost-codes.store'), [
            'name' => 'Office Supplies',
            'code' => 'OS-1001',
        ]);

        $response->assertSessionHasErrors('department_id');
    }

    public function test_store_fails_validation_when_department_id_does_not_exist(): void
    {
        $response = $this->actingAs($this->user)->post(route('cost-codes.store'), [
            'name' => 'Office Supplies',
            'code' => 'OS-1001',
            'department_id' => 99999,
        ]);

        $response->assertSessionHasErrors('department_id');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_edit_form(): void
    {
        $costCode = CostCode::factory()->create();

        $response = $this->actingAs($this->user)->get(route('cost-codes.edit', $costCode));

        $response->assertOk();
        $response->assertViewHas('costCode');
        $response->assertViewHas('departments');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_update_cost_code(): void
    {
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
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorised_user_can_destroy_cost_code(): void
    {
        $costCode = CostCode::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('cost-codes.destroy', $costCode));

        $response->assertRedirect(route('cost-codes.index'));
        $this->assertSoftDeleted('cost_codes', ['id' => $costCode->id]);
    }
}
