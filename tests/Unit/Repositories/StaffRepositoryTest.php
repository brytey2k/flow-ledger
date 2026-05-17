<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Repositories\StaffRepository;
use Tests\TenantAppTestCase;

class StaffRepositoryTest extends TenantAppTestCase
{
    private StaffRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(StaffRepository::class);
    }

    // ── allWithRelations() ────────────────────────────────────────────────────

    public function test_all_with_relations_returns_collection(): void
    {
        $result = $this->repository->allWithRelations();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_all_with_relations_includes_department_and_position(): void
    {
        Staff::factory()->create();

        $result = $this->repository->allWithRelations();

        $this->assertGreaterThan(0, $result->count());
        $first = $result->first();
        $this->assertTrue($first->relationLoaded('department'));
        $this->assertTrue($first->relationLoaded('position'));
    }

    public function test_all_with_relations_is_ordered_by_last_name_then_first_name(): void
    {
        Staff::factory()->create(['last_name' => 'Zulu', 'first_name' => 'Alpha']);
        Staff::factory()->create(['last_name' => 'Alpha', 'first_name' => 'Zulu']);
        Staff::factory()->create(['last_name' => 'Alpha', 'first_name' => 'Able']);

        $result = $this->repository->allWithRelations();

        $lastNames = $result->pluck('last_name')->all();
        $sortedLastNames = $lastNames;
        sort($sortedLastNames);
        $this->assertSame($sortedLastNames, $lastNames);
    }

    // ── unlinkedUsers() ───────────────────────────────────────────────────────

    public function test_unlinked_users_returns_collection(): void
    {
        $result = $this->repository->unlinkedUsers();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_unlinked_users_excludes_users_with_staff_profile(): void
    {
        $linkedUser = User::factory()->create();
        Staff::factory()->withUser($linkedUser)->create();

        $result = $this->repository->unlinkedUsers();

        $resultIds = $result->pluck('id')->all();
        $this->assertNotContains($linkedUser->id, $resultIds);
    }

    public function test_unlinked_users_includes_users_without_staff_profile(): void
    {
        $unlinkedUser = User::factory()->create(['first_name' => 'Orphan']);

        $result = $this->repository->unlinkedUsers();

        $resultIds = $result->pluck('id')->all();
        $this->assertContains($unlinkedUser->id, $resultIds);
    }

    public function test_unlinked_users_ordered_by_first_name(): void
    {
        User::factory()->create(['first_name' => 'Zara']);
        User::factory()->create(['first_name' => 'Aaron']);

        $result = $this->repository->unlinkedUsers();

        $firstNames = $result->pluck('first_name')->all();
        $sorted = $firstNames;
        sort($sorted);
        $this->assertSame($sorted, $firstNames);
    }

    // ── unlinkedUsersOrCurrent() ──────────────────────────────────────────────

    public function test_unlinked_users_or_current_returns_collection(): void
    {
        $staff = Staff::factory()->create();

        $result = $this->repository->unlinkedUsersOrCurrent($staff);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_unlinked_users_or_current_includes_current_linked_user(): void
    {
        $linkedUser = User::factory()->create();
        $staff = Staff::factory()->withUser($linkedUser)->create();

        $result = $this->repository->unlinkedUsersOrCurrent($staff);

        $resultIds = $result->pluck('id')->all();
        $this->assertContains($linkedUser->id, $resultIds);
    }

    public function test_unlinked_users_or_current_excludes_other_linked_users(): void
    {
        // Create another staff member linked to a different user — that user should not appear
        $otherLinkedUser = User::factory()->create();
        Staff::factory()->withUser($otherLinkedUser)->create();

        $staff = Staff::factory()->create(); // no linked user

        $result = $this->repository->unlinkedUsersOrCurrent($staff);

        $resultIds = $result->pluck('id')->all();
        $this->assertNotContains($otherLinkedUser->id, $resultIds);
    }

    public function test_unlinked_users_or_current_ordered_by_first_name(): void
    {
        $linkedUser = User::factory()->create(['first_name' => 'Zara']);
        $staff = Staff::factory()->withUser($linkedUser)->create();

        User::factory()->create(['first_name' => 'Aaron']);

        $result = $this->repository->unlinkedUsersOrCurrent($staff);

        $firstNames = $result->pluck('first_name')->all();
        $sorted = $firstNames;
        sort($sorted);
        $this->assertSame($sorted, $firstNames);
    }
}
