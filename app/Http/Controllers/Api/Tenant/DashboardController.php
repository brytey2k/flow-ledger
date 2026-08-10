<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Services\DashboardAnalyticsService;
use Illuminate\Http\JsonResponse;

class DashboardController extends BaseApiController
{
    public function __construct(
        private readonly DashboardAnalyticsService $dashboardAnalyticsService,
    ) {}

    public function __invoke(): JsonResponse
    {
        $user = $this->apiUser();
        $dashboard = $this->dashboardAnalyticsService->payloadForUser($user);

        return response()->json([
            'data' => [
                // Backward compatibility fields consumed by existing clients.
                'pending_approvals' => $dashboard['summary']['pending_approvals'],
                'my_draft_requests' => $dashboard['personal']['my_draft_requests'],
                'my_in_workflow_requests' => $dashboard['personal']['my_in_workflow_requests'],
                'my_draft_retirements' => $dashboard['personal']['my_draft_retirements'],
                'pending_disbursements' => $dashboard['summary']['pending_disbursements'],
                'low_cash_branches' => $dashboard['insights']['low_cash_branches'],
                'analytics' => $dashboard,
            ],
        ]);
    }
}
