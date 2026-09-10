<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\UserStatus;
use App\Models\Role;
use App\Models\Tenant\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->repository = app(UserRepository::class);
});
test('all with roles returns collection', function () {
    $result = $this->repository->allWithRoles();

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('all with roles eager loads roles relation', function () {
    $result = $this->repository->allWithRoles();

    expect($result->count())->toBeGreaterThan(0);
    expect($result->first()->relationLoaded('roles'))->toBeTrue();
});
test('all with roles includes current user', function () {
    $result = $this->repository->allWithRoles();

    $ids = $result->pluck('id')->all();
    expect($ids)->toContain($this->user->id);
});
test('all with roles orders by created at desc', function () {
    $older = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
        'created_at' => now()->subHour(),
    ]);
    $newer = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
        'created_at' => now(),
    ]);

    $result = $this->repository->allWithRoles();

    $ids = $result->pluck('id')->all();
    expect(array_search($newer->id, $ids))->toBeLessThan(array_search($older->id, $ids));
});
test('all with roles uses descending id as a stable tie breaker', function () {
    $createdAt = now()->subMinute();
    $lowerId = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
        'created_at' => $createdAt,
    ]);
    $higherId = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
        'created_at' => $createdAt,
    ]);

    $ids = $this->repository->allWithRoles()->pluck('id')->all();

    expect(array_search($higherId->id, $ids))->toBeLessThan(array_search($lowerId->id, $ids));
});
test('all with roles filters by search status and role', function () {
    $matchingRole = Role::create(['name' => 'matching_' . Str::uuid(), 'guard_name' => 'web']);
    $otherRole = Role::create(['name' => 'other_' . Str::uuid(), 'guard_name' => 'web']);
    $matchingUser = User::factory()->create([
        'first_name' => 'Searchable',
        'last_name' => 'Person',
        'status' => UserStatus::Active,
    ]);
    $matchingUser->assignRole($matchingRole);

    $wrongRoleUser = User::factory()->create([
        'first_name' => 'Searchable',
        'last_name' => 'Wrong Role',
        'status' => UserStatus::Active,
    ]);
    $wrongRoleUser->assignRole($otherRole);

    $wrongStatusUser = User::factory()->create([
        'first_name' => 'Searchable',
        'last_name' => 'Wrong Status',
        'status' => UserStatus::Suspended,
    ]);
    $wrongStatusUser->assignRole($matchingRole);

    $result = $this->repository->allWithRoles([
        'q' => 'searchable',
        'status' => UserStatus::Active->value,
        'role_id' => $matchingRole->id,
    ]);

    expect($result->pluck('id')->all())->toBe([$matchingUser->id]);
});
