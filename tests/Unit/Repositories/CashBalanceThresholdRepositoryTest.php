<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Tenant\CashBalanceNotificationLog;
use App\Models\Tenant\CashBalanceThreshold;
use App\Repositories\CashBalanceThresholdRepository;
use Tests\TenantAppTestCase;

class CashBalanceThresholdRepositoryTest extends TenantAppTestCase
{
    private CashBalanceThresholdRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(CashBalanceThresholdRepository::class);
    }

    // ── findByBranchId ────────────────────────────────────────────────────────

    public function test_find_by_branch_id_returns_active_threshold(): void
    {
        CashBalanceThreshold::factory()->create([
            'branch_id' => $this->branch->id,
            'threshold_amount' => 500.00,
            'is_active' => true,
        ]);

        $result = $this->repository->findByBranchId($this->branch->id);

        $this->assertNotNull($result);
        $this->assertSame($this->branch->id, $result->branch_id);
    }

    public function test_find_by_branch_id_returns_null_when_no_threshold(): void
    {
        $result = $this->repository->findByBranchId(999999);

        $this->assertNull($result);
    }

    public function test_find_by_branch_id_ignores_inactive_threshold(): void
    {
        CashBalanceThreshold::factory()->create([
            'branch_id' => $this->branch->id,
            'threshold_amount' => 500.00,
            'is_active' => false,
        ]);

        $result = $this->repository->findByBranchId($this->branch->id);

        $this->assertNull($result);
    }

    // ── isBelowThreshold ──────────────────────────────────────────────────────

    public function test_is_below_threshold_returns_true_when_balance_below_threshold(): void
    {
        CashBalanceThreshold::factory()->create([
            'branch_id' => $this->branch->id,
            'threshold_amount' => 1000.00,
            'is_active' => true,
        ]);

        $result = $this->repository->isBelowThreshold($this->branch->id, 500.00);

        $this->assertTrue($result);
    }

    public function test_is_below_threshold_returns_false_when_balance_above_threshold(): void
    {
        CashBalanceThreshold::factory()->create([
            'branch_id' => $this->branch->id,
            'threshold_amount' => 1000.00,
            'is_active' => true,
        ]);

        $result = $this->repository->isBelowThreshold($this->branch->id, 2000.00);

        $this->assertFalse($result);
    }

    public function test_is_below_threshold_returns_false_when_no_threshold_configured(): void
    {
        $result = $this->repository->isBelowThreshold(999999, 100.00);

        $this->assertFalse($result);
    }

    public function test_is_below_threshold_returns_false_when_balance_equals_threshold(): void
    {
        CashBalanceThreshold::factory()->create([
            'branch_id' => $this->branch->id,
            'threshold_amount' => 1000.00,
            'is_active' => true,
        ]);

        $result = $this->repository->isBelowThreshold($this->branch->id, 1000.00);

        $this->assertFalse($result);
    }

    // ── canNotify ─────────────────────────────────────────────────────────────

    public function test_can_notify_returns_true_when_no_previous_notification(): void
    {
        $threshold = CashBalanceThreshold::factory()->create([
            'branch_id' => $this->branch->id,
            'cooldown_minutes' => 60,
            'is_active' => true,
        ]);

        $result = $this->repository->canNotify($threshold->id);

        $this->assertTrue($result);
    }

    public function test_can_notify_returns_false_when_within_cooldown(): void
    {
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

        $this->assertFalse($result);
    }

    public function test_can_notify_returns_true_when_cooldown_has_passed(): void
    {
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

        $this->assertTrue($result);
    }

    public function test_can_notify_returns_false_for_nonexistent_threshold(): void
    {
        $result = $this->repository->canNotify(999999);

        $this->assertFalse($result);
    }

    // ── logNotification ───────────────────────────────────────────────────────

    public function test_log_notification_creates_log_record(): void
    {
        $threshold = CashBalanceThreshold::factory()->create([
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);

        $log = $this->repository->logNotification($threshold->id, 750.00);

        $this->assertInstanceOf(CashBalanceNotificationLog::class, $log);
        $this->assertSame($threshold->id, $log->cash_balance_threshold_id);
    }

    // ── getNotificationUserIds ────────────────────────────────────────────────

    public function test_get_notification_user_ids_returns_null_when_no_threshold(): void
    {
        $result = $this->repository->getNotificationUserIds(999999);

        $this->assertNull($result);
    }

    public function test_get_notification_user_ids_returns_array_of_user_ids(): void
    {
        CashBalanceThreshold::factory()->create([
            'branch_id' => $this->branch->id,
            'notification_user_ids' => [$this->user->id],
            'is_active' => true,
        ]);

        $result = $this->repository->getNotificationUserIds($this->branch->id);

        $this->assertIsArray($result);
        $this->assertContains($this->user->id, $result);
    }

    public function test_get_notification_user_ids_returns_empty_array_when_none_set(): void
    {
        CashBalanceThreshold::factory()->create([
            'branch_id' => $this->branch->id,
            'notification_user_ids' => [],
            'is_active' => true,
        ]);

        $result = $this->repository->getNotificationUserIds($this->branch->id);

        $this->assertSame([], $result);
    }

    // ── getAllActive ──────────────────────────────────────────────────────────

    public function test_get_all_active_returns_only_active_thresholds(): void
    {
        $inactiveBranch = \App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id, 'position' => 95]);

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
            $this->assertTrue((bool) $threshold->is_active);
        }
    }
}
