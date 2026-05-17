<?php

declare(strict_types=1);

namespace Tests\Feature\Staff;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Tests\TenantAppTestCase;

class StaffControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('staff.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_create_staff(): void
    {
        $response = $this->post(route('staff.store'), []);

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessStaff->value);

        $response = $this->actingAs($this->user)->get(route('staff.index'));

        $response->assertForbidden();
    }

    public function test_user_without_permission_cannot_create_staff(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateStaff->value);

        $response = $this->actingAs($this->user)->post(route('staff.store'), []);

        $response->assertForbidden();
    }

    // ── One user per staff (unique constraint) ────────────────────────────────

    public function test_cannot_create_user_with_existing_email_on_staff_create(): void
    {
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
    }

    public function test_cannot_link_already_linked_user_to_another_staff_on_update(): void
    {
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
    }

    public function test_updating_staff_with_existing_linked_user_does_not_change_the_link(): void
    {
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
    }

    // ── CRUD happy paths ──────────────────────────────────────────────────────

    public function test_authorized_user_can_view_staff_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('staff.index'));

        $response->assertOk();
    }

    public function test_authorized_user_can_view_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('staff.create'));

        $response->assertOk();
    }

    public function test_can_create_staff_without_user(): void
    {
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
    }

    public function test_can_update_staff(): void
    {
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
    }

    public function test_can_delete_staff(): void
    {
        $staff = Staff::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('staff.destroy', $staff));

        $response->assertRedirect(route('staff.index'));
        $this->assertDatabaseMissing('staff', ['id' => $staff->id, 'deleted_at' => null]);
    }

    public function test_authorized_user_can_view_edit_form(): void
    {
        $staff = Staff::factory()->create();

        $response = $this->actingAs($this->user)->get(route('staff.edit', $staff));

        $response->assertOk();
    }

    public function test_user_without_permission_cannot_delete_staff(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DeleteStaff->value);
        $staff = Staff::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('staff.destroy', $staff));

        $response->assertForbidden();
    }

    public function test_can_create_staff_with_link_user_action(): void
    {
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
    }

    public function test_can_update_staff_with_link_user_action(): void
    {
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
    }

    public function test_edit_form_for_staff_without_user_includes_unlinked_users(): void
    {
        $staff = Staff::factory()->create(['user_id' => null]);

        $response = $this->actingAs($this->user)->get(route('staff.edit', $staff));

        $response->assertOk();
        $response->assertViewHas('unlinkedUsers');
    }

    public function test_edit_form_for_staff_with_linked_user_has_empty_unlinked_users(): void
    {
        $linkedUser = User::factory()->create();
        $staff = Staff::factory()->withUser($linkedUser)->create();

        $response = $this->actingAs($this->user)->get(route('staff.edit', $staff));

        $response->assertOk();
        $unlinkedUsers = $response->viewData('unlinkedUsers');
        $this->assertCount(0, $unlinkedUsers);
    }

    public function test_can_create_staff_with_create_user_action(): void
    {
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
    }

    public function test_store_fails_validation_when_user_action_is_create_but_email_missing(): void
    {
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
    }

    public function test_update_with_create_user_action_links_new_user_to_staff(): void
    {
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
        $this->assertNotNull($staff->user_id);
    }

    public function test_update_fails_validation_when_user_action_is_create_but_email_missing(): void
    {
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
    }
}
