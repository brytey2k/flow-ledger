<?php

declare(strict_types=1);

namespace Tests\Feature\Users;

use App\Enums\Tenant\PermissionKey;
use App\Models\Permission;
use App\Models\Tenant\User;
use Illuminate\Support\Str;
use Tests\TenantAppTestCase;

class UsersControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('users.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('users.create'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_store(): void
    {
        $this->post(route('users.store'), [])->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_permissions_edit(): void
    {
        $user = User::factory()->create();

        $this->get(route('users.permissions.edit', $user))->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessUsers->value);

        $this->actingAs($this->user)->get(route('users.index'))->assertForbidden();
    }

    public function test_user_without_access_permission_cannot_view_edit(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessUsers->value);
        $user = User::factory()->create();

        $this->actingAs($this->user)->get(route('users.edit', $user))->assertForbidden();
    }

    public function test_user_without_access_permission_cannot_view_permissions_edit(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessUsers->value);
        $user = User::factory()->create();

        $this->actingAs($this->user)->get(route('users.permissions.edit', $user))->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_view_create(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateUser->value);

        $this->actingAs($this->user)->get(route('users.create'))->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_store(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateUser->value);

        $this->actingAs($this->user)->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])->assertForbidden();
    }

    public function test_user_without_delete_permission_cannot_destroy(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DeleteUser->value);
        $user = User::factory()->create();

        $this->actingAs($this->user)->delete(route('users.destroy', $user))->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_index(): void
    {
        $this->actingAs($this->user)->get(route('users.index'))->assertOk();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_create_form_with_roles(): void
    {
        $response = $this->actingAs($this->user)->get(route('users.create'));

        $response->assertOk();
        $response->assertViewHas('roles');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_store_valid_user(): void
    {
        $email = 'newuser-' . Str::uuid() . '@example.com';

        $response = $this->actingAs($this->user)->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => $email,
        ]);
    }

    public function test_store_fails_validation_for_duplicate_email(): void
    {
        $existing = User::factory()->create();

        $response = $this->actingAs($this->user)->post(route('users.store'), [
            'first_name' => 'Jane',
            'last_name' => 'Doe',
            'email' => $existing->email,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_store_fails_validation_when_passwords_do_not_match(): void
    {
        $response = $this->actingAs($this->user)->post(route('users.store'), [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'mismatch-' . Str::uuid() . '@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different456',
        ]);

        $response->assertSessionHasErrors('password');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_edit_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)->get(route('users.edit', $user));

        $response->assertOk();
        $response->assertViewHas('user');
        $response->assertViewHas('roles');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_update_user_name(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)->put(route('users.update', $user), [
            'first_name' => 'Updated',
            'last_name' => 'Name',
            'email' => $user->email,
        ]);

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Updated',
            'last_name' => 'Name',
        ]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorised_user_can_destroy_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)->delete(route('users.destroy', $user));

        $response->assertRedirect(route('users.index'));
        $this->assertDatabaseMissing('users', ['id' => $user->id, 'deleted_at' => null]);
    }

    // ── Permissions Edit ──────────────────────────────────────────────────────

    public function test_authorised_user_can_view_permissions_edit_form(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($this->user)->get(route('users.permissions.edit', $user));

        $response->assertOk();
        $response->assertViewHas('user');
        $response->assertViewHas('permissions');
    }

    // ── Permissions Update ────────────────────────────────────────────────────

    public function test_permissions_update_syncs_permissions_to_user(): void
    {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate(PermissionKey::AccessAccountCodes->value, 'web');

        $response = $this->actingAs($this->user)->put(route('users.permissions.update', $user), [
            'permissions' => [$permission->id],
        ]);

        $response->assertRedirect(route('users.edit', $user));
        $this->assertTrue($user->fresh()->hasPermissionTo($permission->name));
    }

    public function test_permissions_update_with_empty_array_removes_all_permissions(): void
    {
        $user = User::factory()->create();
        $permission = Permission::findOrCreate(PermissionKey::AccessAccountCodes->value, 'web');
        $user->givePermissionTo($permission);

        $response = $this->actingAs($this->user)->put(route('users.permissions.update', $user), [
            'permissions' => [],
        ]);

        $response->assertRedirect(route('users.edit', $user));
        $this->assertFalse($user->fresh()->hasPermissionTo($permission->name));
    }
}
