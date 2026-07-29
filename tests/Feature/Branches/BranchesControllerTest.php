<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Department;
use App\Models\Tenant\Level;
use App\Models\Tenant\Staff;

function validBranchPayload(Level $level, Currency $currency, Branch|null $parent = null): array
{
    return [
        'name' => 'Test Branch',
        'code' => 'TB-001',
        'level_id' => $level->id,
        'currency_id' => $currency->id,
        'parent_id' => $parent?->id,
    ];
}
test('guest is redirected from index', function () {
    $this->get(route('branches.index'))->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $this->get(route('branches.create'))->assertRedirect(route('login'));
});
test('guest cannot store branch', function () {
    $this->post(route('branches.store'), [])->assertRedirect(route('login'));
});
test('user without access branches cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessBranches->value);

    $this->actingAs($this->user)->get(route('branches.index'))->assertForbidden();
});
test('user without access branches cannot view edit form', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessBranches->value);
    $branch = Branch::factory()->create();

    $this->actingAs($this->user)->get(route('branches.edit', $branch))->assertForbidden();
});
test('user without create branch cannot view create form', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateBranch->value);

    $this->actingAs($this->user)->get(route('branches.create'))->assertForbidden();
});
test('user without create branch cannot store', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateBranch->value);

    $this->actingAs($this->user)->post(route('branches.store'), [])->assertForbidden();
});
test('authorized user can view branches index', function () {
    $this->actingAs($this->user)->get(route('branches.index'))->assertOk();
});
test('authorized user can view create form', function () {
    $this->actingAs($this->user)->get(route('branches.create'))
        ->assertOk()
        ->assertViewHas('levels')
        ->assertViewHas('currencies');
});
test('authorized user can store a root branch', function () {
    $level = Level::factory()->create();
    $currency = Currency::factory()->create();

    $this->actingAs($this->user)
        ->post(route('branches.store'), validBranchPayload($level, $currency))
        ->assertRedirect(route('branches.index'));

    $this->assertDatabaseHas('branches', ['name' => 'Test Branch', 'level_id' => $level->id]);
});
test('storing a branch logs activity', function () {
    $level = Level::factory()->create();
    $currency = Currency::factory()->create();

    $this->actingAs($this->user)->post(route('branches.store'), validBranchPayload($level, $currency));

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'branch',
        'event' => 'branch.created',
    ]);
});
test('store fails validation when name is missing', function () {
    $level = Level::factory()->create();
    $currency = Currency::factory()->create();

    $payload = validBranchPayload($level, $currency);
    unset($payload['name']);

    $this->actingAs($this->user)
        ->post(route('branches.store'), $payload)
        ->assertSessionHasErrors('name');
});
test('authorized user can view edit form', function () {
    $branch = Branch::factory()->create();

    $this->actingAs($this->user)->get(route('branches.edit', $branch))->assertOk();
});
test('authorized user can update a branch', function () {
    $level = Level::factory()->create();
    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);

    $payload = validBranchPayload($level, $currency);
    $payload['name'] = 'Updated Branch Name';

    $this->actingAs($this->user)
        ->put(route('branches.update', $branch), $payload)
        ->assertRedirect(route('branches.index'));

    $this->assertDatabaseHas('branches', ['id' => $branch->id, 'name' => 'Updated Branch Name']);
});
test('updating a branch logs activity', function () {
    $level = Level::factory()->create();
    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);

    $payload = validBranchPayload($level, $currency);
    $payload['name'] = 'Logged Branch Name';

    $this->actingAs($this->user)->put(route('branches.update', $branch), $payload);

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'branch',
        'subject_id' => $branch->id,
        'event' => 'branch.updated',
    ]);
});
test('authorized user can delete branch with no staff and no children', function () {
    $branch = Branch::factory()->create();

    $this->actingAs($this->user)
        ->delete(route('branches.destroy', $branch))
        ->assertRedirect(route('branches.index'));

    $this->assertSoftDeleted('branches', ['id' => $branch->id]);
});
test('destroying a branch logs activity', function () {
    $branch = Branch::factory()->create();

    $this->actingAs($this->user)->delete(route('branches.destroy', $branch));

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => 'branch',
        'subject_id' => $branch->id,
        'event' => 'branch.deleted',
    ]);
});
test('cannot delete branch that has staff', function () {
    $branch = Branch::factory()->create();
    $dept = Department::factory()->create();
    Staff::factory()->create(['branch_id' => $branch->id, 'department_id' => $dept->id]);

    $this->actingAs($this->user)
        ->delete(route('branches.destroy', $branch))
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseHas('branches', ['id' => $branch->id, 'deleted_at' => null]);
});
test('cannot delete branch that has child branches', function () {
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
});
test('authorized user can store a child branch', function () {
    $level = Level::factory()->create();
    $currency = Currency::factory()->create();
    $parent = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);

    $this->actingAs($this->user)
        ->post(route('branches.store'), validBranchPayload($level, $currency, $parent))
        ->assertRedirect(route('branches.index'));

    $this->assertDatabaseHas('branches', ['name' => 'Test Branch', 'parent_id' => $parent->id]);
});
test('update moves branch to new parent', function () {
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
    expect($branch->parent_id)->toEqual($newParent->id);
});
test('update branch to root when parent id is null', function () {
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
});
