<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Level;

test('guest is redirected from index', function () {
    $response = $this->get(route('levels.index'));

    $response->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $response = $this->get(route('levels.create'));

    $response->assertRedirect(route('login'));
});
test('guest cannot post to store', function () {
    $response = $this->post(route('levels.store'), ['name' => 'Head Office', 'position' => 1]);

    $response->assertRedirect(route('login'));
});
test('user without access permission cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessLevels->value);

    $response = $this->actingAs($this->user)->get(route('levels.index'));

    $response->assertForbidden();
});
test('user without create permission cannot access create form', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateLevel->value);

    $response = $this->actingAs($this->user)->get(route('levels.create'));

    $response->assertForbidden();
});
test('authorised user can view index', function () {
    $response = $this->actingAs($this->user)->get(route('levels.index'));

    $response->assertOk();
});
test('authorised user can view create form', function () {
    $response = $this->actingAs($this->user)->get(route('levels.create'));

    $response->assertOk();
});
test('authorised user can store level', function () {
    $response = $this->actingAs($this->user)->post(route('levels.store'), [
        'name' => 'Regional Office',
        'position' => 2,
    ]);

    $response->assertRedirect(route('levels.index'));
    $this->assertDatabaseHas('levels', ['name' => 'Regional Office', 'position' => 2]);
});
test('store fails validation for missing required fields', function () {
    $response = $this->actingAs($this->user)->post(route('levels.store'), []);

    $response->assertSessionHasErrors(['name', 'position']);
});
test('authorised user can view edit form', function () {
    $level = Level::factory()->create();

    $response = $this->actingAs($this->user)->get(route('levels.edit', $level));

    $response->assertOk();
});
test('authorised user can update level', function () {
    $level = Level::factory()->create(['name' => 'Old Name', 'position' => 5]);

    $response = $this->actingAs($this->user)->put(route('levels.update', $level), [
        'name' => 'New Name',
        'position' => 3,
    ]);

    $response->assertRedirect(route('levels.index'));
    $this->assertDatabaseHas('levels', ['id' => $level->id, 'name' => 'New Name', 'position' => 3]);
});
test('authorised user can delete level', function () {
    $level = Level::factory()->create();

    $response = $this->actingAs($this->user)->delete(route('levels.destroy', $level));

    $response->assertRedirect(route('levels.index'));
    $this->assertSoftDeleted('levels', ['id' => $level->id]);
});
test('delete is blocked when level has branches', function () {
    $level = Level::factory()->create();
    Branch::factory()->create(['level_id' => $level->id]);

    $response = $this->actingAs($this->user)->delete(route('levels.destroy', $level));

    $response->assertRedirect();
    $this->assertDatabaseHas('levels', ['id' => $level->id]);
});
