<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\CashbookBalanceChanged;
use App\Models\Tenant\User;
use App\Notifications\LowCashBalanceNotification;
use App\Repositories\CashBalanceThresholdRepository;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Support\Facades\Notification;
use Throwable;

/**
 * Runs once the cashbook change has committed, and never throws: a low-balance alert is a side effect, so a
 * failure here (mail transport, queue, a bad recipient) is reported but cannot undo or fail the disbursement,
 * settlement or receipt that moved the balance.
 */
class CheckCashBalanceThreshold implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private readonly CashBalanceThresholdRepository $thresholdRepository,
    ) {}

    public function handle(CashbookBalanceChanged $event): void
    {
        try {
            $this->notifyIfBelowThreshold($event);
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function notifyIfBelowThreshold(CashbookBalanceChanged $event): void
    {
        /** @var int $branchId */
        $branchId = $event->cashbook->getAttribute('branch_id');
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
        /** @var int $thresholdId */
        $thresholdId = $threshold->getKey();
        if (! $this->thresholdRepository->canNotify($thresholdId)) {
            return;
        }

        // Get notification recipients
        $userIds = $this->thresholdRepository->getNotificationUserIds($branchId);

        if (empty($userIds)) {
            return;
        }

        // Get users and send notifications
        $users = User::query()
            ->active()
            ->whereIn('id', $userIds)
            ->get();

        if ($users->isEmpty()) {
            return;
        }

        // Log the notification
        $this->thresholdRepository->logNotification(
            $thresholdId,
            $newBalance,
        );

        // Send notifications
        Notification::send(
            $users,
            new LowCashBalanceNotification($threshold, (float) $newBalance),
        );
    }
}
