<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\WorkflowTemplateRepository;
use Tests\TenantAppTestCase;

class WorkflowTemplateRepositoryTest extends TenantAppTestCase
{
    private WorkflowTemplateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(WorkflowTemplateRepository::class);
    }

    // ── allWithStageCount ─────────────────────────────────────────────────────

    public function test_all_with_stage_count_returns_collection(): void
    {
        $result = $this->repository->allWithStageCount();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_all_with_stage_count_includes_stage_count_attribute(): void
    {
        WorkflowTemplate::factory()->advance()->create(['name' => 'Advance Template']);

        $result = $this->repository->allWithStageCount();

        $this->assertGreaterThan(0, $result->count());
        $first = $result->first();
        $this->assertTrue($first->relationLoaded('branch') || $first->branch_id === null);
        $this->assertNotNull($first->stages_count);
    }

    public function test_all_with_stage_count_orders_by_name(): void
    {
        WorkflowTemplate::factory()->advance()->create(['name' => 'Zulu Template']);
        WorkflowTemplate::factory()->advance()->create(['name' => 'Alpha Template']);
        WorkflowTemplate::factory()->advance()->create(['name' => 'Mango Template']);

        $result = $this->repository->allWithStageCount();

        $names = $result->pluck('name')->all();
        $sorted = $names;
        sort($sorted);
        $this->assertSame($sorted, $names);
    }

    public function test_all_with_stage_count_eager_loads_branch_relation(): void
    {
        WorkflowTemplate::factory()->advance()->create();

        $result = $this->repository->allWithStageCount();

        $this->assertGreaterThan(0, $result->count());
        $first = $result->first();
        $this->assertTrue($first->relationLoaded('branch'));
    }

    public function test_all_with_stage_count_returns_zero_count_for_template_without_stages(): void
    {
        WorkflowTemplate::factory()->advance()->create(['name' => 'Empty Template']);

        $result = $this->repository->allWithStageCount();

        $emptyTemplate = $result->firstWhere('name', 'Empty Template');
        $this->assertNotNull($emptyTemplate);
        $this->assertSame(0, $emptyTemplate->stages_count);
    }
}
