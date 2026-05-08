<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\RetirementRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class RetirementRequestRepository
{
    /** @return LengthAwarePaginator<int, RetirementRequest> */
    public function paginated(int $perPage = 20): LengthAwarePaginator
    {
        return RetirementRequest::with(['paymentRequest.staff', 'paymentRequest.currency'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function findWithDetails(int|string $id): RetirementRequest
    {
        return RetirementRequest::with([
            'paymentRequest.staff',
            'paymentRequest.branch',
            'paymentRequest.currency',
            'items.accountCode',
            'attachments.user',
            'activeWorkflowInstance.instanceStages.stage.roles',
            'activities.causer',
            'comments.user',
        ])->findOrFail($id);
    }
}
