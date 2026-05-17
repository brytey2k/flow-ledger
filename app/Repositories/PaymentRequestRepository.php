<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\PaymentRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRequestRepository
{
    /**
     * @param array<int, int> $branchIds
     * @param int $perPage
     *
     * @return LengthAwarePaginator<int, PaymentRequest>
     */
    public function paginated(array $branchIds, int $perPage = 20): LengthAwarePaginator
    {
        return PaymentRequest::with(['staff', 'branch', 'currency'])
            ->whereIn('branch_id', $branchIds)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * @param array<int, int> $branchIds
     * @param int $perPage
     *
     * @return LengthAwarePaginator<int, PaymentRequest>
     */
    public function pendingDisbursement(array $branchIds, int $perPage = 20): LengthAwarePaginator
    {
        return PaymentRequest::with(['staff', 'branch', 'currency'])
            ->whereIn('branch_id', $branchIds)
            ->where('status', 'approved')
            ->orderBy('approved_at', 'asc')
            ->paginate($perPage);
    }

    public function findWithDetails(int|string $id): PaymentRequest
    {
        return PaymentRequest::with([
            'staff',
            'branch',
            'currency',
            'items.accountCode',
            'activeWorkflowInstance.instanceStages.stage.roles',
            'activities.causer',
            'comments.user',
        ])->findOrFail($id);
    }
}
