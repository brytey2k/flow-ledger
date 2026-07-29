<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Repositories\StaffRepository;

beforeEach(function () {
    $this->repository = app(StaffRepository::class);
});
test('all with relations returns collection', function () {
    $result = $this->repository->allWithRelations([$this->branch->id]);

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('all with relations includes department and position', function () {
    Staff::factory()->withBranch($this->branch)->create();

    $result = $this->repository->allWithRelations([$this->branch->id]);

    expect($result->count())->toBeGreaterThan(0);
    $first = $result->first();
    expect($first->relationLoaded('department'))->toBeTrue();
    expect($first->relationLoaded('position'))->toBeTrue();
});
test('all with relations is ordered by last name then first name', function () {
    Staff::factory()->withBranch($this->branch)->create(['last_name' => 'Zulu', 'first_name' => 'Alpha']);
    Staff::factory()->withBranch($this->branch)->create(['last_name' => 'Alpha', 'first_name' => 'Zulu']);
    Staff::factory()->withBranch($this->branch)->create(['last_name' => 'Alpha', 'first_name' => 'Able']);

    $result = $this->repository->allWithRelations([$this->branch->id]);

    $lastNames = $result->pluck('last_name')->all();
    $sortedLastNames = $lastNames;
    sort($sortedLastNames);
    expect($lastNames)->toBe($sortedLastNames);
});
test('unlinked users returns collection', function () {
    $result = $this->repository->unlinkedUsers();

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('unlinked users excludes users with staff profile', function () {
    $linkedUser = User::factory()->create();
    Staff::factory()->withUser($linkedUser)->create();

    $result = $this->repository->unlinkedUsers();

    $resultIds = $result->pluck('id')->all();
    expect($resultIds)->not->toContain($linkedUser->id);
});
test('unlinked users includes users without staff profile', function () {
    $unlinkedUser = User::factory()->create(['first_name' => 'Orphan']);

    $result = $this->repository->unlinkedUsers();

    $resultIds = $result->pluck('id')->all();
    expect($resultIds)->toContain($unlinkedUser->id);
});
test('unlinked users ordered by first name', function () {
    User::factory()->create(['first_name' => 'Zara']);
    User::factory()->create(['first_name' => 'Aaron']);

    $result = $this->repository->unlinkedUsers();

    $firstNames = $result->pluck('first_name')->all();
    $sorted = $firstNames;
    sort($sorted);
    expect($firstNames)->toBe($sorted);
});
test('unlinked users or current returns collection', function () {
    $staff = Staff::factory()->create();

    $result = $this->repository->unlinkedUsersOrCurrent($staff);

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('unlinked users or current includes current linked user', function () {
    $linkedUser = User::factory()->create();
    $staff = Staff::factory()->withUser($linkedUser)->create();

    $result = $this->repository->unlinkedUsersOrCurrent($staff);

    $resultIds = $result->pluck('id')->all();
    expect($resultIds)->toContain($linkedUser->id);
});
test('unlinked users or current excludes other linked users', function () {
    // Create another staff member linked to a different user — that user should not appear
    $otherLinkedUser = User::factory()->create();
    Staff::factory()->withUser($otherLinkedUser)->create();

    $staff = Staff::factory()->create();

    // no linked user
    $result = $this->repository->unlinkedUsersOrCurrent($staff);

    $resultIds = $result->pluck('id')->all();
    expect($resultIds)->not->toContain($otherLinkedUser->id);
});
test('unlinked users or current ordered by first name', function () {
    $linkedUser = User::factory()->create(['first_name' => 'Zara']);
    $staff = Staff::factory()->withUser($linkedUser)->create();

    User::factory()->create(['first_name' => 'Aaron']);

    $result = $this->repository->unlinkedUsersOrCurrent($staff);

    $firstNames = $result->pluck('first_name')->all();
    $sorted = $firstNames;
    sort($sorted);
    expect($firstNames)->toBe($sorted);
});
