<?php

declare(strict_types=1);

namespace Tests\Feature\Roles;

use App\Enums\Tenant\PermissionKey;
use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant\User;
use Illuminate\Support\Str;
use Tests\TenantAppTestCase;

class RolesControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('roles.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('roles.create'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_store(): void
    {
        $this->post(route('roles.store'), [])->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_permissions_edit(): void
    {
        $role = Role::create(['name' => 'guest-test-' . Str::uuid(), 'guard_name' => 'web']);

        $this->get(route('roles.permissions.edit', $role))->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessRoles->value);

        $this->actingAs($this->user)->get(route('roles.index'))->assertForbidden();
    }

    public function test_user_without_access_permission_cannot_view_edit(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessRoles->value);
        $role = Role::create(['name' => 'auth-test-' . Str::uuid(), 'guard_name' => 'web']);

        $this->actingAs($this->user)->get(route('roles.edit', $role))->assertForbidden();
    }

    public function test_user_without_access_permission_cannot_view_permissions_edit(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessRoles->value);
        $role = Role::create(['name' => 'perm-test-' . Str::uuid(), 'guard_name' => 'web']);

        $this->actingAs($this->user)->get(route('roles.permissions.edit', $role))->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_view_create(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateRole->value);

        $this->actingAs($this->user)->get(route('roles.create'))->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_store(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateRole->value);

        $this->actingAs($this->user)->post(route('roles.store'), ['name' => 'Manager'])->assertForbidden();
    }

    public function test_user_without_delete_permission_cannot_destroy(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DeleteRole->value);
        $role = Role::create(['name' => 'delete-test-' . Str::uuid(), 'guard_name' => 'web']);

        $this->actingAs($this->user)->delete(route('roles.destroy', $role))->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_index(): void
    {
        $this->actingAs($this->user)->get(route('roles.index'))->assertOk();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_create_form(): void
    {
        $this->actingAs($this->user)->get(route('roles.create'))->assertOk();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_store_valid_role(): void
    {
        $roleName = 'Manager-' . Str::uuid();

        $response = $this->actingAs($this->user)->post(route('roles.store'), [
            'name' => $roleName,
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['name' => $roleName]);
    }

    public function test_store_fails_validation_when_name_is_missing(): void
    {
        $response = $this->actingAs($this->user)->post(route('roles.store'), ['name' => '']);

        $response->assertSessionHasErrors('name');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_view_edit_form(): void
    {
        $role = Role::create(['name' => 'edit-form-' . Str::uuid(), 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)->get(route('roles.edit', $role));

        $response->assertOk();
        $response->assertViewHas('role');
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_authorised_user_can_update_role_name(): void
    {
        $role = Role::create(['name' => 'original-name-' . Str::uuid(), 'guard_name' => 'web']);
        $newName = 'updated-name-' . Str::uuid();

        $response = $this->actingAs($this->user)->put(route('roles.update', $role), [
            'name' => $newName,
        ]);

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['id' => $role->id, 'name' => $newName]);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorised_user_can_destroy_role_with_no_users(): void
    {
        $role = Role::create(['name' => 'destroy-me-' . Str::uuid(), 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)->delete(route('roles.destroy', $role));

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseMissing('roles', ['id' => $role->id]);
    }

    public function test_destroy_is_blocked_when_role_has_users(): void
    {
        $role = Role::create(['name' => 'has-users-' . Str::uuid(), 'guard_name' => 'web']);
        $user = User::factory()->create();
        $user->assignRole($role);

        $response = $this->actingAs($this->user)->delete(route('roles.destroy', $role));

        $response->assertRedirect(route('roles.index'));
        $this->assertDatabaseHas('roles', ['id' => $role->id]);
    }

    // ── Permissions Edit ──────────────────────────────────────────────────────

    public function test_authorised_user_can_view_permissions_edit_form(): void
    {
        $role = Role::create(['name' => 'perm-edit-' . Str::uuid(), 'guard_name' => 'web']);

        $response = $this->actingAs($this->user)->get(route('roles.permissions.edit', $role));

        $response->assertOk();
        $response->assertViewHas('role');
        $response->assertViewHas('permissions');
    }

    // ── Permissions Update ────────────────────────────────────────────────────

    public function test_permissions_update_syncs_permissions_to_role(): void
    {
        $role = Role::create(['name' => 'sync-test-' . Str::uuid(), 'guard_name' => 'web']);
        $permission = Permission::findOrCreate(PermissionKey::AccessAccountCodes->value, 'web');

        $response = $this->actingAs($this->user)->put(route('roles.permissions.update', $role), [
            'permissions' => [$permission->id],
        ]);

        $response->assertRedirect(route('roles.edit', $role));
        $this->assertTrue($role->fresh()->hasPermissionTo($permission->name));
    }

    public function test_permissions_update_with_empty_array_removes_all_permissions(): void
    {
        $role = Role::create(['name' => 'remove-perms-' . Str::uuid(), 'guard_name' => 'web']);
        $permission = Permission::findOrCreate(PermissionKey::AccessAccountCodes->value, 'web');
        $role->givePermissionTo($permission);

        $response = $this->actingAs($this->user)->put(route('roles.permissions.update', $role), [
            'permissions' => [],
        ]);

        $response->assertRedirect(route('roles.edit', $role));
        $this->assertFalse($role->fresh()->hasPermissionTo($permission->name));
    }
}
