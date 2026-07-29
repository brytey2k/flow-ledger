<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\WorkflowTemplateRepository;

beforeEach(function () {
    $this->repository = app(WorkflowTemplateRepository::class);
});
test('all with stage count returns collection', function () {
    $result = $this->repository->allWithStageCount();

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('all with stage count includes stage count attribute', function () {
    WorkflowTemplate::factory()->advance()->create(['name' => 'Advance Template']);

    $result = $this->repository->allWithStageCount();

    expect($result->count())->toBeGreaterThan(0);
    $first = $result->first();
    expect($first->relationLoaded('branch') || $first->branch_id === null)->toBeTrue();
    expect($first->stages_count)->not->toBeNull();
});
test('all with stage count orders by name', function () {
    WorkflowTemplate::factory()->advance()->create(['name' => 'Zulu Template']);
    WorkflowTemplate::factory()->advance()->create(['name' => 'Alpha Template']);
    WorkflowTemplate::factory()->advance()->create(['name' => 'Mango Template']);

    $result = $this->repository->allWithStageCount();

    $names = $result->pluck('name')->all();
    $sorted = $names;
    sort($sorted);
    expect($names)->toBe($sorted);
});
test('all with stage count eager loads branch relation', function () {
    WorkflowTemplate::factory()->advance()->create();

    $result = $this->repository->allWithStageCount();

    expect($result->count())->toBeGreaterThan(0);
    $first = $result->first();
    expect($first->relationLoaded('branch'))->toBeTrue();
});
test('all with stage count returns zero count for template without stages', function () {
    WorkflowTemplate::factory()->advance()->create(['name' => 'Empty Template']);

    $result = $this->repository->allWithStageCount();

    $emptyTemplate = $result->firstWhere('name', 'Empty Template');
    expect($emptyTemplate)->not->toBeNull();
    expect($emptyTemplate->stages_count)->toBe(0);
});
