<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\RetirementRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RetirementRequestRepository
{
    /**
     * @param array<int, int> $branchIds
     * @param int $perPage
     *
     * @return LengthAwarePaginator<int, RetirementRequest>
     */
    public function paginated(array $branchIds, int $perPage = 20): LengthAwarePaginator
    {
        return RetirementRequest::with(['paymentRequest.staff', 'paymentRequest.currency'])
            ->whereHas('paymentRequest', fn($q) => $q->whereIn('branch_id', $branchIds))
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findWithDetails(int|string $id): RetirementRequest
    {
        return RetirementRequest::with([
            'paymentRequest.staff',
            'paymentRequest.branch',
            'paymentRequest.currency',
            'items.costCode',
            'attachments.user',
            'activeWorkflowInstance.instanceStages.stage.roles',
            'activities.causer',
            'comments.user',
        ])->findOrFail($id);
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string|null $dateFrom
     * @param string|null $dateTo
     *
     * @return EloquentCollection<int, RetirementRequest>
     */
    public function retirementStatuses(array $allowedBranchIds, string|null $dateFrom = null, string|null $dateTo = null): EloquentCollection
    {
        return RetirementRequest::whereHas('paymentRequest', fn(EloquentBuilder $query) => $query->whereIn('branch_id', $allowedBranchIds))
            ->when($dateFrom !== null && $dateTo !== null, fn($q) => $q->whereBetween('created_at', [$dateFrom, $dateTo]))
            ->select('status', DB::raw('COUNT(*) as count, SUM(total_amount_expended) as total'))
            ->groupBy('status')
            ->orderBy('status')
            ->get();
    }

    /**
     * @param array<int, int> $allowedBranchIds
     * @param string $dateFrom
     * @param string $dateTo
     * @param int|string|null $branchId
     *
     * @return Collection<int, \stdClass>
     */
    public function varianceRows(
        array $allowedBranchIds,
        string $dateFrom,
        string $dateTo,
        int|string|null $branchId,
    ): Collection {
        return DB::table('retirement_requests')
            ->join('payment_requests', 'retirement_requests.payment_request_id', '=', 'payment_requests.id')
            ->join('staff', 'payment_requests.staff_id', '=', 'staff.id')
            ->join('branches', 'payment_requests.branch_id', '=', 'branches.id')
            ->join('currencies', 'payment_requests.currency_id', '=', 'currencies.id')
            ->whereIn('payment_requests.branch_id', $allowedBranchIds)
            ->whereIn('retirement_requests.status', ['approved', 'settled'])
            ->whereBetween('retirement_requests.approved_at', [$dateFrom, $dateTo])
            ->whereNull('retirement_requests.deleted_at')
            ->when($branchId, fn(QueryBuilder $q) => $q->where('payment_requests.branch_id', $branchId))
            ->select([
                'retirement_requests.id',
                'payment_requests.id as payment_request_id',
                'payment_requests.total_amount as disbursed_amount',
                'retirement_requests.total_amount_expended',
                'retirement_requests.difference_amount',
                'retirement_requests.difference_type',
                'retirement_requests.approved_at',
                DB::raw("CONCAT(staff.first_name, ' ', staff.last_name) as staff_name"),
                'branches.name as branch_name',
                'currencies.short_name as currency_code',
            ])
            ->orderByDesc('retirement_requests.approved_at')
            ->get();
    }
}
