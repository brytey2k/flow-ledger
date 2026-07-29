<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Department;

test('branches returns scoped list', function () {
    $response = $this->getJson('/api/branches')->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($this->branch->id);
});
test('currencies returns all', function () {
    Currency::factory()->count(3)->create();

    $this->getJson('/api/currencies')
        ->assertOk()
        ->assertJsonStructure(['data'])
        ->assertJsonCount(3, 'data');
});
test('cost codes returns all', function () {
    CostCode::factory()->count(2)->create();

    $this->getJson('/api/cost-codes')
        ->assertOk()
        ->assertJsonStructure(['data'])
        ->assertJsonCount(2, 'data');
});
test('departments returns all', function () {
    Department::factory()->count(2)->create();

    $this->getJson('/api/departments')
        ->assertOk()
        ->assertJsonStructure(['data'])
        ->assertJsonCount(2, 'data');
});
