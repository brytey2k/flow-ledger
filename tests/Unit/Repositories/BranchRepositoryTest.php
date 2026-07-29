<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Repositories\BranchRepository;

beforeEach(function () {
    $this->repository = app(BranchRepository::class);
});
test('all with relations returns collection', function () {
    $result = $this->repository->allWithRelations();

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('all with relations eager loads level', function () {
    $result = $this->repository->allWithRelations();

    expect($result->count())->toBeGreaterThan(0);
    expect($result->first()->relationLoaded('level'))->toBeTrue();
});
test('all with cashbook returns collection', function () {
    $result = $this->repository->allWithCashbook();

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('all with cashbook eager loads currency', function () {
    $result = $this->repository->allWithCashbook();

    expect($result->count())->toBeGreaterThan(0);
    expect($result->first()->relationLoaded('currency'))->toBeTrue();
});
test('all ordered by name returns branches sorted', function () {
    Branch::factory()->create(['name' => 'Zulu Branch', 'level_id' => $this->level->id, 'position' => 99]);
    Branch::factory()->create(['name' => 'Alpha Branch', 'level_id' => $this->level->id, 'position' => 98]);

    $result = $this->repository->allOrderedByName();

    $names = $result->pluck('name')->all();
    $sorted = $names;
    sort($sorted);
    expect($names)->toBe($sorted);
});
test('all by ids ordered by name returns only requested ids', function () {
    $branch2 = Branch::factory()->create(['name' => 'Branch Two', 'level_id' => $this->level->id, 'position' => 97]);

    $result = $this->repository->allByIdsOrderedByName([$this->branch->id]);

    expect($result->toArray())->toHaveKey($this->branch->id);
    $this->assertArrayNotHasKey($branch2->id, $result->toArray());
});
test('all by ids ordered by name returns name keyed by id', function () {
    $result = $this->repository->allByIdsOrderedByName([$this->branch->id]);

    expect($result->get($this->branch->id))->toBeString();
});
test('all except excludes given branch', function () {
    $result = $this->repository->allExcept($this->branch->id);

    $ids = $result->pluck('id')->all();
    expect($ids)->not->toContain($this->branch->id);
});
test('all except includes other branches', function () {
    $otherBranch = Branch::factory()->create(['level_id' => $this->level->id, 'position' => 96]);

    $result = $this->repository->allExcept($this->branch->id);

    $ids = $result->pluck('id')->all();
    expect($ids)->toContain($otherBranch->id);
});
test('find or fail returns branch', function () {
    $branch = $this->repository->findOrFail($this->branch->id);

    expect($branch)->toBeInstanceOf(Branch::class);
    expect($branch->id)->toBe($this->branch->id);
});
test('find or fail throws for nonexistent id', function () {
    $this->expectException(Illuminate\Database\Eloquent\ModelNotFoundException::class);

    $this->repository->findOrFail(999999);
});
