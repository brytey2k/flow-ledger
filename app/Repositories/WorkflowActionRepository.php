<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Tenant\WorkflowAction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Carbon;
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
            ->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
            ->where('created_at', '<', Carbon::parse($dateTo)->startOfDay()->addDay())
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
            ->where('created_at', '>=', Carbon::parse($dateFrom)->startOfDay())
            ->where('created_at', '<', Carbon::parse($dateTo)->startOfDay()->addDay())
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
        $start = Carbon::parse($dateFrom)->startOfDay();
        $endExclusive = Carbon::parse($dateTo)->startOfDay()->addDay();

        return WorkflowAction::with([
            'user',
            'instanceStage.stage',
            'instanceStage.instance.workflowable',
        ])
            ->where('created_at', '>=', $start)
            ->where('created_at', '<', $endExclusive)
            ->when($action, fn($query) => $query->where('action', $action))
            ->latest('created_at')
            ->paginate($perPage)
            ->withQueryString();
    }
}
