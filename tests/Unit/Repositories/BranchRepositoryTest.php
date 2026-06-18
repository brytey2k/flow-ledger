<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Tenant\Branch;
use App\Repositories\BranchRepository;
use Tests\TenantAppTestCase;

class BranchRepositoryTest extends TenantAppTestCase
{
    private BranchRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(BranchRepository::class);
    }

    // ── allWithRelations ──────────────────────────────────────────────────────

    public function test_all_with_relations_returns_collection(): void
    {
        $result = $this->repository->allWithRelations();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_all_with_relations_eager_loads_level(): void
    {
        $result = $this->repository->allWithRelations();

        $this->assertGreaterThan(0, $result->count());
        $this->assertTrue($result->first()->relationLoaded('level'));
    }

    // ── allWithCashbook ───────────────────────────────────────────────────────

    public function test_all_with_cashbook_returns_collection(): void
    {
        $result = $this->repository->allWithCashbook();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_all_with_cashbook_eager_loads_currency(): void
    {
        $result = $this->repository->allWithCashbook();

        $this->assertGreaterThan(0, $result->count());
        $this->assertTrue($result->first()->relationLoaded('currency'));
    }

    // ── allOrderedByName ──────────────────────────────────────────────────────

    public function test_all_ordered_by_name_returns_branches_sorted(): void
    {
        Branch::factory()->create(['name' => 'Zulu Branch', 'level_id' => $this->level->id, 'position' => 99]);
        Branch::factory()->create(['name' => 'Alpha Branch', 'level_id' => $this->level->id, 'position' => 98]);

        $result = $this->repository->allOrderedByName();

        $names = $result->pluck('name')->all();
        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);
    }

    // ── allByIdsOrderedByName ─────────────────────────────────────────────────

    public function test_all_by_ids_ordered_by_name_returns_only_requested_ids(): void
    {
        $branch2 = Branch::factory()->create(['name' => 'Branch Two', 'level_id' => $this->level->id, 'position' => 97]);

        $result = $this->repository->allByIdsOrderedByName([$this->branch->id]);

        $this->assertArrayHasKey($this->branch->id, $result->toArray());
        $this->assertArrayNotHasKey($branch2->id, $result->toArray());
    }

    public function test_all_by_ids_ordered_by_name_returns_name_keyed_by_id(): void
    {
        $result = $this->repository->allByIdsOrderedByName([$this->branch->id]);

        $this->assertIsString($result->get($this->branch->id));
    }

    // ── allExcept ─────────────────────────────────────────────────────────────

    public function test_all_except_excludes_given_branch(): void
    {
        $result = $this->repository->allExcept($this->branch->id);

        $ids = $result->pluck('id')->all();
        $this->assertNotContains($this->branch->id, $ids);
    }

    public function test_all_except_includes_other_branches(): void
    {
        $otherBranch = Branch::factory()->create(['level_id' => $this->level->id, 'position' => 96]);

        $result = $this->repository->allExcept($this->branch->id);

        $ids = $result->pluck('id')->all();
        $this->assertContains($otherBranch->id, $ids);
    }

    // ── findOrFail ────────────────────────────────────────────────────────────

    public function test_find_or_fail_returns_branch(): void
    {
        $branch = $this->repository->findOrFail($this->branch->id);

        $this->assertInstanceOf(Branch::class, $branch);
        $this->assertSame($this->branch->id, $branch->id);
    }

    public function test_find_or_fail_throws_for_nonexistent_id(): void
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->repository->findOrFail(999999);
    }
}
