<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Tenant\User;
use App\Repositories\UserRepository;
use Tests\TenantAppTestCase;

class UserRepositoryTest extends TenantAppTestCase
{
    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(UserRepository::class);
    }

    // ── allWithRoles ──────────────────────────────────────────────────────────

    public function test_all_with_roles_returns_collection(): void
    {
        $result = $this->repository->allWithRoles();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_all_with_roles_eager_loads_roles_relation(): void
    {
        $result = $this->repository->allWithRoles();

        $this->assertGreaterThan(0, $result->count());
        $this->assertTrue($result->first()->relationLoaded('roles'));
    }

    public function test_all_with_roles_includes_current_user(): void
    {
        $result = $this->repository->allWithRoles();

        $ids = $result->pluck('id')->all();
        $this->assertContains($this->user->id, $ids);
    }

    public function test_all_with_roles_orders_by_created_at_desc(): void
    {
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
        $this->assertLessThan(array_search($older->id, $ids), array_search($newer->id, $ids));
    }
}
