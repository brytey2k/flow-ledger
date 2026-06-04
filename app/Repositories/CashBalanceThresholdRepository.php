<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\CashBalanceNotificationLog;
use App\Models\Tenant\CashBalanceThreshold;

class CashBalanceThresholdRepository
{
    public function findByBranchId(int $branchId): CashBalanceThreshold|null
    {
        return CashBalanceThreshold::where('branch_id', $branchId)
            ->where('is_active', true)
            ->first();
    }

    public function isBelowThreshold(int $branchId, float $currentBalance): bool
    {
        $threshold = $this->findByBranchId($branchId);

        if (! $threshold) {
            return false;
        }

        $thresholdAmount = (float) $threshold->getAttribute('threshold_amount');

        return $currentBalance < $thresholdAmount;
    }

    public function canNotify(int $thresholdId): bool
    {
        $threshold = CashBalanceThreshold::find($thresholdId);

        if (! $threshold) {
            return false;
        }

        $cooldownMinutes = (int) $threshold->getAttribute('cooldown_minutes');
        $lastNotification = $threshold->notificationLogs()
            ->latest('notified_at')
            ->first();

        if (! $lastNotification) {
            return true;
        }

        $lastNotifiedAt = $lastNotification->getAttribute('notified_at');

        return $lastNotifiedAt->addMinutes($cooldownMinutes)->isPast();
    }

    public function logNotification(int $thresholdId, float $balanceAmount): CashBalanceNotificationLog
    {
        return CashBalanceNotificationLog::create([
            'cash_balance_threshold_id' => $thresholdId,
            'balance_amount' => $balanceAmount,
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

        return $threshold->getAttribute('notification_user_ids') ?? [];
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
