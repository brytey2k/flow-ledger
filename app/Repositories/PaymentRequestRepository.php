<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\PaymentRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PaymentRequestRepository
{
    /** @return LengthAwarePaginator<int, PaymentRequest> */
    public function paginated(int $perPage = 20): LengthAwarePaginator
    {
        return PaymentRequest::with(['staff', 'branch', 'currency'])
            ->orderBy('created_at', 'desc')
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
