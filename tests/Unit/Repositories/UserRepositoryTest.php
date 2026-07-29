<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\User;
use App\Repositories\UserRepository;

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
