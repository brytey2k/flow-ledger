<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class WorkflowInstanceRepository
{
    /** @return LengthAwarePaginator<int, WorkflowInstanceStage> */
    public function activeStagesForUser(User $user, int $perPage = 20): LengthAwarePaginator
    {
        $roleIds = $user->roles()->pluck('id');

        return WorkflowInstanceStage::query()
            ->where('status', 'active')
            ->whereHas('stage.roles', fn($q) => $q->whereIn('roles.id', $roleIds))
            ->with([
                'stage',
                'instance.workflowable.staff',
                'instance.workflowable.branch',
                'instance.workflowable.currency',
            ])
            ->latest()
            ->paginate($perPage);
    }
}
