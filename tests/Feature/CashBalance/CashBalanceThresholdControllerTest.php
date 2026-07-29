<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CashBalanceThreshold;
use App\Models\Tenant\Currency;
use App\Models\Tenant\User;

test('guest is redirected from threshold settings', function () {
    $this->get(route('cash-balance-thresholds.index'))
        ->assertRedirect(route('login'));
});
test('user without access settings cannot view threshold settings', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);

    $this->actingAs($this->user)
        ->get(route('cash-balance-thresholds.index'))
        ->assertForbidden();
});
test('user can view threshold settings', function () {
    $currency = Currency::factory()->create(['symbol' => '₵']);
    $branch = Branch::factory()->create([
        'currency_id' => $currency->id,
        'level_id' => $this->level->id,
    ]);
    CashBalanceThreshold::factory()->create([
        'branch_id' => $branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('cash-balance-thresholds.index'))
        ->assertOk()
        ->assertSee(__('cash_balance.title'));
});
test('user can create update and delete thresholds', function () {
    $currency = Currency::factory()->create(['symbol' => '₵']);
    $branch = Branch::factory()->create([
        'currency_id' => $currency->id,
        'level_id' => $this->level->id,
    ]);
    $recipient = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)->post(route('cash-balance-thresholds.store'), [
        'branch_id' => $branch->id,
        'threshold_amount' => 2500.50,
        'notification_user_ids' => [$recipient->id],
        'cooldown_minutes' => 90,
        'is_active' => true,
    ])->assertRedirect(route('cash-balance-thresholds.index'));

    $threshold = CashBalanceThreshold::where('branch_id', $branch->id)->firstOrFail();
    expect($threshold->threshold_amount)->toBe('2500.50');
    expect($threshold->notification_user_ids)->toBe([$recipient->id]);

    $this->actingAs($this->user)->put(route('cash-balance-thresholds.update', $threshold), [
        'threshold_amount' => 1200.00,
        'notification_user_ids' => [],
        'cooldown_minutes' => 30,
        'is_active' => false,
    ])->assertRedirect(route('cash-balance-thresholds.index'));

    $threshold->refresh();
    expect($threshold->threshold_amount)->toBe('1200.00');
    expect($threshold->is_active)->toBeFalse();

    $this->actingAs($this->user)->delete(route('cash-balance-thresholds.destroy', $threshold))
        ->assertRedirect(route('cash-balance-thresholds.index'));

    $this->assertModelMissing($threshold);
});
test('search users returns matching users as json', function () {
    $alice = User::factory()->create([
        'first_name' => 'Alice',
        'last_name' => 'Smith',
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    User::factory()->create([
        'first_name' => 'Bob',
        'last_name' => 'Jones',
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson(route('cash-balance-thresholds.users', ['q' => 'Ali']))
        ->assertOk();

    $data = $response->json();
    $ids = array_column($data, 'id');

    expect($ids)->toContain($alice->id);
    expect(array_filter($ids, fn($id) => $id === $alice->id))->toHaveCount(1);

    foreach ($data as $item) {
        expect($item)->toHaveKey('id');
        expect($item)->toHaveKey('text');
    }
});
test('search users without query returns up to 20 users', function () {
    $this->actingAs($this->user)
        ->getJson(route('cash-balance-thresholds.users'))
        ->assertOk()
        ->assertJsonStructure([['id', 'text']]);
});
test('guest cannot search users', function () {
    $this->getJson(route('cash-balance-thresholds.users'))
        ->assertUnauthorized();
});
test('user without access settings cannot search users', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessSettings->value);

    $this->actingAs($this->user)
        ->getJson(route('cash-balance-thresholds.users'))
        ->assertForbidden();
});
