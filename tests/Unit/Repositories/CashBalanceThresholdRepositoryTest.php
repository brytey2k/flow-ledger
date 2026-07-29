<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\CashBalanceNotificationLog;
use App\Models\Tenant\CashBalanceThreshold;
use App\Repositories\CashBalanceThresholdRepository;

beforeEach(function () {
    $this->repository = app(CashBalanceThresholdRepository::class);
});
test('find by branch id returns active threshold', function () {
    CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'threshold_amount' => 500.00,
        'is_active' => true,
    ]);

    $result = $this->repository->findByBranchId($this->branch->id);

    expect($result)->not->toBeNull();
    expect($result->branch_id)->toBe($this->branch->id);
});
test('find by branch id returns null when no threshold', function () {
    $result = $this->repository->findByBranchId(999999);

    expect($result)->toBeNull();
});
test('find by branch id ignores inactive threshold', function () {
    CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'threshold_amount' => 500.00,
        'is_active' => false,
    ]);

    $result = $this->repository->findByBranchId($this->branch->id);

    expect($result)->toBeNull();
});
test('is below threshold returns true when balance below threshold', function () {
    CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'threshold_amount' => 1000.00,
        'is_active' => true,
    ]);

    $result = $this->repository->isBelowThreshold($this->branch->id, 500.00);

    expect($result)->toBeTrue();
});
test('is below threshold returns false when balance above threshold', function () {
    CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'threshold_amount' => 1000.00,
        'is_active' => true,
    ]);

    $result = $this->repository->isBelowThreshold($this->branch->id, 2000.00);

    expect($result)->toBeFalse();
});
test('is below threshold returns false when no threshold configured', function () {
    $result = $this->repository->isBelowThreshold(999999, 100.00);

    expect($result)->toBeFalse();
});
test('is below threshold returns false when balance equals threshold', function () {
    CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'threshold_amount' => 1000.00,
        'is_active' => true,
    ]);

    $result = $this->repository->isBelowThreshold($this->branch->id, 1000.00);

    expect($result)->toBeFalse();
});
test('can notify returns true when no previous notification', function () {
    $threshold = CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'cooldown_minutes' => 60,
        'is_active' => true,
    ]);

    $result = $this->repository->canNotify($threshold->id);

    expect($result)->toBeTrue();
});
test('can notify returns false when within cooldown', function () {
    $threshold = CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'cooldown_minutes' => 1440,
        'is_active' => true,
    ]);

    CashBalanceNotificationLog::factory()->create([
        'cash_balance_threshold_id' => $threshold->id,
        'notified_at' => now()->subMinutes(30),
    ]);

    $result = $this->repository->canNotify($threshold->id);

    expect($result)->toBeFalse();
});
test('can notify returns true when cooldown has passed', function () {
    $threshold = CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'cooldown_minutes' => 60,
        'is_active' => true,
    ]);

    CashBalanceNotificationLog::factory()->create([
        'cash_balance_threshold_id' => $threshold->id,
        'notified_at' => now()->subHours(2),
    ]);

    $result = $this->repository->canNotify($threshold->id);

    expect($result)->toBeTrue();
});
test('can notify returns false for nonexistent threshold', function () {
    $result = $this->repository->canNotify(999999);

    expect($result)->toBeFalse();
});
test('log notification creates log record', function () {
    $threshold = CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    $log = $this->repository->logNotification($threshold->id, 750.00);

    expect($log)->toBeInstanceOf(CashBalanceNotificationLog::class);
    expect($log->cash_balance_threshold_id)->toBe($threshold->id);
});
test('get notification user ids returns null when no threshold', function () {
    $result = $this->repository->getNotificationUserIds(999999);

    expect($result)->toBeNull();
});
test('get notification user ids returns array of user ids', function () {
    CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'notification_user_ids' => [$this->user->id],
        'is_active' => true,
    ]);

    $result = $this->repository->getNotificationUserIds($this->branch->id);

    expect($result)->toBeArray();
    expect($result)->toContain($this->user->id);
});
test('get notification user ids returns empty array when none set', function () {
    CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'notification_user_ids' => [],
        'is_active' => true,
    ]);

    $result = $this->repository->getNotificationUserIds($this->branch->id);

    expect($result)->toBe([]);
});
test('get all active returns only active thresholds', function () {
    $inactiveBranch = App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id, 'position' => 95]);

    CashBalanceThreshold::factory()->create([
        'branch_id' => $this->branch->id,
        'is_active' => true,
    ]);

    CashBalanceThreshold::factory()->create([
        'branch_id' => $inactiveBranch->id,
        'is_active' => false,
    ]);

    $result = $this->repository->getAllActive();

    foreach ($result as $threshold) {
        expect((bool) $threshold->is_active)->toBeTrue();
    }
});
