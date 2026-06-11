<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\WorkflowAction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;

class WorkflowActionRepository
{
    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return EloquentCollection<int, WorkflowAction>
     */
    public function workflowActionTotals(string $dateFrom, string $dateTo): EloquentCollection
    {
        return WorkflowAction::with('user')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('user_id', DB::raw('COUNT(*) as total_actions'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return EloquentCollection<int, WorkflowAction>
     */
    public function workflowActionSentBackTotals(string $dateFrom, string $dateTo): EloquentCollection
    {
        return WorkflowAction::with('user')
            ->where('action', 'sent_back')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->select('user_id', DB::raw('COUNT(*) as sent_back_count'))
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     * @param ?string $action
     * @param int $perPage
     *
     * @return LengthAwarePaginator<int, WorkflowAction>
     */
    public function auditTrail(string $dateFrom, string $dateTo, string|null $action, int $perPage = 50): LengthAwarePaginator
    {
        return WorkflowAction::with([
            'user',
            'instanceStage.stage',
            'instanceStage.instance.workflowable',
        ])
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->when($action, fn($query) => $query->where('action', $action))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
