<?php

declare(strict_types=1);

namespace Tests\Feature\Departments;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Department;
use Tests\TenantAppTestCase;

class DepartmentsControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('departments.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $response = $this->get(route('departments.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_post_to_store(): void
    {
        $response = $this->post(route('departments.store'), ['name' => 'Finance']);

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessDepartments->value);

        $response = $this->actingAs($this->user)->get(route('departments.index'));

        $response->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_access_create_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateDepartment->value);

        $response = $this->actingAs($this->user)->get(route('departments.create'));

        $response->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('departments.index'));

        $response->assertOk();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('departments.create'));

        $response->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_store_department(): void
    {
        $response = $this->actingAs($this->user)->post(route('departments.store'), [
            'name' => 'Finance',
        ]);

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', ['name' => 'Finance']);
    }

    public function test_store_fails_validation_for_missing_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('departments.store'), []);

        $response->assertSessionHasErrors('name');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_edit_form(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->user)->get(route('departments.edit', $department));

        $response->assertOk();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_update_department(): void
    {
        $department = Department::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($this->user)->put(route('departments.update', $department), [
            'name' => 'New Name',
        ]);

        $response->assertRedirect(route('departments.index'));
        $this->assertDatabaseHas('departments', ['id' => $department->id, 'name' => 'New Name']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorised_user_can_delete_department(): void
    {
        $department = Department::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('departments.destroy', $department));

        $response->assertRedirect(route('departments.index'));
        $this->assertSoftDeleted('departments', ['id' => $department->id]);
    }
}
