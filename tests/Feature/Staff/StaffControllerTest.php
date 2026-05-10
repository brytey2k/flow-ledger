<?php

declare(strict_types=1);

namespace Tests\Feature\Staff;

use App\Enums\Tenant\PermissionKey;
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

    public function test_cannot_link_same_user_to_two_staff_on_create(): void
    {
        $linkedUser = User::factory()->create();
        Staff::factory()->withUser($linkedUser)->create();

        $staff = Staff::factory()->make();

        $response = $this->actingAs($this->user)->post(route('staff.store'), [
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'email' => $staff->email,
            'department_id' => $staff->department_id,
            'position_id' => $staff->position_id,
            'user_id' => $linkedUser->id,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_cannot_link_same_user_to_two_staff_on_update(): void
    {
        $linkedUser = User::factory()->create();
        Staff::factory()->withUser($linkedUser)->create();

        $otherStaff = Staff::factory()->create();

        $response = $this->actingAs($this->user)->put(route('staff.update', $otherStaff), [
            'first_name' => $otherStaff->first_name,
            'last_name' => $otherStaff->last_name,
            'department_id' => $otherStaff->department_id,
            'position_id' => $otherStaff->position_id,
            'user_id' => $linkedUser->id,
        ]);

        $response->assertSessionHasErrors('user_id');
    }

    public function test_staff_can_keep_their_own_linked_user_on_update(): void
    {
        $linkedUser = User::factory()->create();
        $staff = Staff::factory()->withUser($linkedUser)->create();

        $response = $this->actingAs($this->user)->put(route('staff.update', $staff), [
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'department_id' => $staff->department_id,
            'position_id' => $staff->position_id,
            'user_id' => $linkedUser->id,
        ]);

        $response->assertSessionDoesntHaveErrors('user_id');
    }
}
