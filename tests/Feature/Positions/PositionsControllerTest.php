<?php

declare(strict_types=1);

namespace Tests\Feature\Positions;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Department;
use App\Models\Tenant\Position;
use App\Models\Tenant\Staff;
use Tests\TenantAppTestCase;

class PositionsControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('positions.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $response = $this->get(route('positions.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_post_to_store(): void
    {
        $response = $this->post(route('positions.store'), ['name' => 'Manager']);

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessPositions->value);

        $response = $this->actingAs($this->user)->get(route('positions.index'));

        $response->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_access_create_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreatePosition->value);

        $response = $this->actingAs($this->user)->get(route('positions.create'));

        $response->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('positions.index'));

        $response->assertOk();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('positions.create'));

        $response->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_store_position(): void
    {
        $response = $this->actingAs($this->user)->post(route('positions.store'), [
            'name' => 'Manager',
        ]);

        $response->assertRedirect(route('positions.index'));
        $this->assertDatabaseHas('positions', ['name' => 'Manager']);
    }

    public function test_store_fails_validation_for_missing_name(): void
    {
        $response = $this->actingAs($this->user)->post(route('positions.store'), []);

        $response->assertSessionHasErrors('name');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_edit_form(): void
    {
        $position = Position::factory()->create();

        $response = $this->actingAs($this->user)->get(route('positions.edit', $position));

        $response->assertOk();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_update_position(): void
    {
        $position = Position::factory()->create(['name' => 'Old Title']);

        $response = $this->actingAs($this->user)->put(route('positions.update', $position), [
            'name' => 'New Title',
        ]);

        $response->assertRedirect(route('positions.index'));
        $this->assertDatabaseHas('positions', ['id' => $position->id, 'name' => 'New Title']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorised_user_can_delete_position(): void
    {
        $position = Position::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('positions.destroy', $position));

        $response->assertRedirect(route('positions.index'));
        $this->assertSoftDeleted('positions', ['id' => $position->id]);
    }

    public function test_delete_is_blocked_when_position_has_staff(): void
    {
        $position = Position::factory()->create();
        $department = Department::factory()->create();
        Staff::factory()->create(['position_id' => $position->id, 'department_id' => $department->id]);

        $response = $this->actingAs($this->user)->delete(route('positions.destroy', $position));

        $response->assertRedirect();
        $this->assertDatabaseHas('positions', ['id' => $position->id]);
    }
}
