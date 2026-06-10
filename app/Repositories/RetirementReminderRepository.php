<?php

declare(strict_types=1);

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RetirementReminderRepository
{
    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     *
     * @return Collection<int, \stdClass>
     */
    public function reportRows(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
    ): Collection {
        return DB::table('retirement_reminder_logs')
            ->join('payment_requests', 'retirement_reminder_logs.payment_request_id', '=', 'payment_requests.id')
            ->join('staff', 'payment_requests.staff_id', '=', 'staff.id')
            ->join('branches', 'payment_requests.branch_id', '=', 'branches.id')
            ->whereNull('payment_requests.deleted_at')
            ->whereIn('payment_requests.branch_id', $allowedBranchIds)
            ->whereBetween('retirement_reminder_logs.notified_date', [$dateFrom, $dateTo])
            ->when($branchId, fn($query) => $query->where('payment_requests.branch_id', $branchId))
            ->selectRaw("
                payment_requests.id as payment_request_id,
                branches.name as branch_name,
                CONCAT(staff.first_name, ' ', staff.last_name) as staff_name,
                payment_requests.total_amount,
                payment_requests.disbursed_at,
                COUNT(*) as reminder_count,
                MAX(retirement_reminder_logs.notified_date) as last_reminder_date
            ")
            ->groupBy(
                'payment_requests.id',
                'branches.name',
                'staff.first_name',
                'staff.last_name',
                'payment_requests.total_amount',
                'payment_requests.disbursed_at',
            )
            ->orderByDesc('reminder_count')
            ->get();
    }
}
