<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Level;

test('cashbook relation loads associated cashbook', function () {
    $branch = Branch::factory()->create();
    $currency = Currency::factory()->create();
    $cashbook = Cashbook::create([
        'branch_id' => $branch->id,
        'currency_id' => $currency->id,
        'balance' => '0.00',
    ]);

    expect($branch->cashbook->id)->toEqual($cashbook->id);
});
test('currency relation loads currency', function () {
    $level = Level::factory()->create();
    $currency = Currency::factory()->create();
    $branch = Branch::factory()->create(['level_id' => $level->id, 'currency_id' => $currency->id]);

    expect($branch->currency->id)->toEqual($currency->id);
});
test('level relation loads level', function () {
    $level = Level::factory()->create();
    $branch = Branch::factory()->create(['level_id' => $level->id]);

    expect($branch->level->id)->toEqual($level->id);
});
