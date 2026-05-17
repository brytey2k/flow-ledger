<?php

declare(strict_types=1);

namespace Tests\Feature\Levels;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Level;
use Tests\TenantAppTestCase;

class LevelsControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('levels.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $response = $this->get(route('levels.create'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_post_to_store(): void
    {
        $response = $this->post(route('levels.store'), ['name' => 'Head Office', 'position' => 1]);

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessLevels->value);

        $response = $this->actingAs($this->user)->get(route('levels.index'));

        $response->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_access_create_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateLevel->value);

        $response = $this->actingAs($this->user)->get(route('levels.create'));

        $response->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_index(): void
    {
        $response = $this->actingAs($this->user)->get(route('levels.index'));

        $response->assertOk();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_create_form(): void
    {
        $response = $this->actingAs($this->user)->get(route('levels.create'));

        $response->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_store_level(): void
    {
        $response = $this->actingAs($this->user)->post(route('levels.store'), [
            'name' => 'Regional Office',
            'position' => 2,
        ]);

        $response->assertRedirect(route('levels.index'));
        $this->assertDatabaseHas('levels', ['name' => 'Regional Office', 'position' => 2]);
    }

    public function test_store_fails_validation_for_missing_required_fields(): void
    {
        $response = $this->actingAs($this->user)->post(route('levels.store'), []);

        $response->assertSessionHasErrors(['name', 'position']);
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_edit_form(): void
    {
        $level = Level::factory()->create();

        $response = $this->actingAs($this->user)->get(route('levels.edit', $level));

        $response->assertOk();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_update_level(): void
    {
        $level = Level::factory()->create(['name' => 'Old Name', 'position' => 5]);

        $response = $this->actingAs($this->user)->put(route('levels.update', $level), [
            'name' => 'New Name',
            'position' => 3,
        ]);

        $response->assertRedirect(route('levels.index'));
        $this->assertDatabaseHas('levels', ['id' => $level->id, 'name' => 'New Name', 'position' => 3]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorised_user_can_delete_level(): void
    {
        $level = Level::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('levels.destroy', $level));

        $response->assertRedirect(route('levels.index'));
        $this->assertSoftDeleted('levels', ['id' => $level->id]);
    }

    public function test_delete_is_blocked_when_level_has_branches(): void
    {
        $level = Level::factory()->create();
        Branch::factory()->create(['level_id' => $level->id]);

        $response = $this->actingAs($this->user)->delete(route('levels.destroy', $level));

        $response->assertRedirect();
        $this->assertDatabaseHas('levels', ['id' => $level->id]);
    }
}
