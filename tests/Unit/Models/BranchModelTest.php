<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Level;
use Tests\TenantAppTestCase;

class BranchModelTest extends TenantAppTestCase
{
    public function test_cashbook_relation_loads_associated_cashbook(): void
    {
        $branch = Branch::factory()->create();
        $currency = Currency::factory()->create();
        $cashbook = Cashbook::create([
            'branch_id' => $branch->id,
            'currency_id' => $currency->id,
            'balance' => '0.00',
        ]);

        $this->assertEquals($cashbook->id, $branch->cashbook->id);
    }

    public function test_currency_relation_loads_currency(): void
    {
        $level = Level::factory()->create();
        $currency = Currency::factory()->create();
        $branch = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);

        $this->assertEquals($currency->id, $branch->currency->id);
    }

    public function test_level_relation_loads_level(): void
    {
        $level = Level::factory()->create();
        $branch = Branch::factory()->create(['level_id' => $level->id]);

        $this->assertEquals($level->id, $branch->level->id);
    }
}
