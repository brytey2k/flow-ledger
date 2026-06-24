<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Department;
use Tests\ApiTenantTestCase;

class ReferenceDataControllerTest extends ApiTenantTestCase
{
    // ── Branches ──────────────────────────────────────────────────────────────

    public function test_branches_returns_scoped_list(): void
    {
        $response = $this->getJson('/api/branches')->assertOk();

        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($this->branch->id, $ids);
    }

    // ── Currencies ────────────────────────────────────────────────────────────

    public function test_currencies_returns_all(): void
    {
        Currency::factory()->count(3)->create();

        $this->getJson('/api/currencies')
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(3, 'data');
    }

    // ── Cost Codes ────────────────────────────────────────────────────────────

    public function test_cost_codes_returns_all(): void
    {
        CostCode::factory()->count(2)->create();

        $this->getJson('/api/cost-codes')
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(2, 'data');
    }

    // ── Departments ───────────────────────────────────────────────────────────

    public function test_departments_returns_all(): void
    {
        Department::factory()->count(2)->create();

        $this->getJson('/api/departments')
            ->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(2, 'data');
    }
}
