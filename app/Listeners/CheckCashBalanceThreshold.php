<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CashbookBalanceChanged;
use App\Models\Tenant\User;
use App\Notifications\LowCashBalanceNotification;
use App\Repositories\CashBalanceThresholdRepository;
use Illuminate\Support\Facades\Notification;

class CheckCashBalanceThreshold
{
    public function __construct(
        private readonly CashBalanceThresholdRepository $thresholdRepository,
    ) {}

    public function handle(CashbookBalanceChanged $event): void
    {
        $branchId = (int) $event->cashbook->getAttribute('branch_id');
        $newBalance = $event->newBalance;

        // Check if balance has fallen below the threshold
        if (! $this->thresholdRepository->isBelowThreshold($branchId, $newBalance)) {
            return;
        }

        $threshold = $this->thresholdRepository->findByBranchId($branchId);

        if (! $threshold) {
            return;
        }

        // Check if we can notify (cooldown enforcement)
        if (! $this->thresholdRepository->canNotify((int) $threshold->getAttribute('id'))) {
            return;
        }

        // Get notification recipients
        $userIds = $this->thresholdRepository->getNotificationUserIds($branchId);

        if (empty($userIds)) {
            return;
        }

        // Get users and send notifications
        $users = User::whereIn('id', $userIds)->get();

        if ($users->isEmpty()) {
            return;
        }

        // Log the notification
        $this->thresholdRepository->logNotification(
            (int) $threshold->getAttribute('id'),
            $newBalance,
        );

        // Send notifications
        Notification::send(
            $users,
            new LowCashBalanceNotification($threshold, $newBalance),
        );
    }
}
