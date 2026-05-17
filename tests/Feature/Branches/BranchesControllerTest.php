<?php

declare(strict_types=1);

namespace Tests\Feature\Branches;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Department;
use App\Models\Tenant\Level;
use App\Models\Tenant\Staff;
use Tests\TenantAppTestCase;

class BranchesControllerTest extends TenantAppTestCase
{
    private function validBranchPayload(Level $level, Currency $currency, Branch|null $parent = null): array
    {
        return [
            'name' => 'Test Branch',
            'code' => 'TB-001',
            'level_id' => $level->id,
            'currency_id' => $currency->id,
            'parent_id' => $parent?->id,
        ];
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('branches.index'))->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('branches.create'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_branch(): void
    {
        $this->post(route('branches.store'), [])->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_branches_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessBranches->value);

        $this->actingAs($this->user)->get(route('branches.index'))->assertForbidden();
    }

    public function test_user_without_access_branches_cannot_view_edit_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessBranches->value);
        $branch = Branch::factory()->create();

        $this->actingAs($this->user)->get(route('branches.edit', $branch))->assertForbidden();
    }

    public function test_user_without_create_branch_cannot_view_create_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateBranch->value);

        $this->actingAs($this->user)->get(route('branches.create'))->assertForbidden();
    }

    public function test_user_without_create_branch_cannot_store(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateBranch->value);

        $this->actingAs($this->user)->post(route('branches.store'), [])->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorized_user_can_view_branches_index(): void
    {
        $this->actingAs($this->user)->get(route('branches.index'))->assertOk();
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authorized_user_can_view_create_form(): void
    {
        $this->actingAs($this->user)->get(route('branches.create'))
            ->assertOk()
            ->assertViewHas('levels')
            ->assertViewHas('currencies');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authorized_user_can_store_a_root_branch(): void
    {
        $level = Level::factory()->create();
        $currency = Currency::factory()->create();

        $this->actingAs($this->user)
            ->post(route('branches.store'), $this->validBranchPayload($level, $currency))
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', ['name' => 'Test Branch', 'level_id' => $level->id]);
    }

    public function test_store_fails_validation_when_name_is_missing(): void
    {
        $level = Level::factory()->create();
        $currency = Currency::factory()->create();

        $payload = $this->validBranchPayload($level, $currency);
        unset($payload['name']);

        $this->actingAs($this->user)
            ->post(route('branches.store'), $payload)
            ->assertSessionHasErrors('name');
    }

    // ── Edit ──────────────────────────────────────────────────────────────────

    public function test_authorized_user_can_view_edit_form(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->user)->get(route('branches.edit', $branch))->assertOk();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_authorized_user_can_update_a_branch(): void
    {
        $level = Level::factory()->create();
        $currency = Currency::factory()->create();
        $branch = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);

        $payload = $this->validBranchPayload($level, $currency);
        $payload['name'] = 'Updated Branch Name';

        $this->actingAs($this->user)
            ->put(route('branches.update', $branch), $payload)
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Updated Branch Name']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_authorized_user_can_delete_branch_with_no_staff_and_no_children(): void
    {
        $branch = Branch::factory()->create();

        $this->actingAs($this->user)
            ->delete(route('branches.destroy', $branch))
            ->assertRedirect(route('branches.index'));

        $this->assertSoftDeleted('branches', ['id' => $branch->id]);
    }

    public function test_cannot_delete_branch_that_has_staff(): void
    {
        $branch = Branch::factory()->create();
        $dept = Department::factory()->create();
        Staff::factory()->create(['branch_id' => $branch->id, 'department_id' => $dept->id]);

        $this->actingAs($this->user)
            ->delete(route('branches.destroy', $branch))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);
    }

    public function test_cannot_delete_branch_that_has_child_branches(): void
    {
        $level = Level::factory()->create();
        $currency = Currency::factory()->create();
        $parent = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);
        Branch::factory()->create([
            'level_id' => $level->id,
            'currency_id' => $currency->id,
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($this->user)
            ->delete(route('branches.destroy', $parent))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertDatabaseHas('branches', ['id' => $parent->id, 'deleted_at' => null]);
    }

    public function test_authorized_user_can_store_a_child_branch(): void
    {
        $level = Level::factory()->create();
        $currency = Currency::factory()->create();
        $parent = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);

        $this->actingAs($this->user)
            ->post(route('branches.store'), $this->validBranchPayload($level, $currency, $parent))
            ->assertRedirect(route('branches.index'));

        $this->assertDatabaseHas('branches', ['name' => 'Test Branch', 'parent_id' => $parent->id]);
    }

    public function test_update_moves_branch_to_new_parent(): void
    {
        $level = Level::factory()->create();
        $currency = Currency::factory()->create();
        $newParent = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);
        $branch = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);

        $this->actingAs($this->user)
            ->put(route('branches.update', $branch), [
                'name' => $branch->name,
                'code' => $branch->code,
                'level_id' => $level->id,
                'currency_id' => $currency->id,
                'parent_id' => $newParent->id,
            ])
            ->assertRedirect(route('branches.index'));

        $branch->refresh();
        $this->assertEquals($newParent->id, $branch->parent_id);
    }

    public function test_update_branch_to_root_when_parent_id_is_null(): void
    {
        $level = Level::factory()->create();
        $currency = Currency::factory()->create();
        $parent = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);
        $child = Branch::factory()->create([
            'level_id' => $level->id,
            'currency_id' => $currency->id,
            'parent_id' => $parent->id,
        ]);

        $this->actingAs($this->user)
            ->put(route('branches.update', $child), [
                'name' => $child->name,
                'code' => $child->code,
                'level_id' => $level->id,
                'currency_id' => $currency->id,
                'parent_id' => null,
            ])
            ->assertRedirect(route('branches.index'));
    }
}
