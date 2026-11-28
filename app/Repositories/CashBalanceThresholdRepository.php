<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\CashBalanceNotificationLog;
use App\Models\Tenant\CashBalanceThreshold;
use App\Support\Money;

class CashBalanceThresholdRepository
{
    public function findByBranchId(int $branchId): CashBalanceThreshold|null
    {
        return CashBalanceThreshold::where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
    }

    /**
     * @param int $branchId
     * @param numeric-string $currentBalance
     */
    public function isBelowThreshold(int $branchId, string $currentBalance): bool
    {
        $threshold = $this->findByBranchId($branchId);

        if (! $threshold) {
            return false;
        }

        return bccomp($currentBalance, Money::of($threshold->getAttribute('threshold_amount')), 2) < 0;
    }

    public function canNotify(int $thresholdId): bool
    {
        $threshold = CashBalanceThreshold::find($thresholdId);

        if (! $threshold) {
            return false;
        }

        /** @var int $cooldownMinutes */
        $cooldownMinutes = $threshold->getAttribute('cooldown_minutes');
        $lastNotification = $threshold->notificationLogs()
            ->latest('notified_at')
            ->first();

        if (! $lastNotification) {
            return true;
        }

        /** @var \Illuminate\Support\Carbon $lastNotifiedAt */
        $lastNotifiedAt = $lastNotification->getAttribute('notified_at');

        return $lastNotifiedAt->addMinutes($cooldownMinutes)->isPast();
    }

    /**
     * @param int $thresholdId
     * @param numeric-string $balanceAmount
     */
    public function logNotification(int $thresholdId, string $balanceAmount): CashBalanceNotificationLog
    {
        return CashBalanceNotificationLog::create([
            'cash_balance_threshold_id' => $thresholdId,
            'balance_amount' => $balanceAmount,
            'notified_at' => now(),
        ]);
    }

    /**
     * @param int $branchId
     *
     * @return array<int>|null
     */
    public function getNotificationUserIds(int $branchId): array|null
    {
        $threshold = $this->findByBranchId($branchId);

        if (! $threshold) {
            return null;
        }

        $ids = $threshold->getAttribute('notification_user_ids');

        return is_array($ids) ? array_map(static fn(mixed $v): int => is_scalar($v) ? (int) $v : 0, $ids) : [];
    }

    /**
     * Get all active thresholds
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, CashBalanceThreshold>
     */
    public function getAllActive()
    {
        return CashBalanceThreshold::where('is_active', true)
            ->with(['branch.currency', 'notificationLogs'])
            ->get();
    }
}
