<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Services\DashboardAnalyticsService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardAnalyticsService $dashboardAnalyticsService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        assert($user instanceof \App\Models\Tenant\User);
        $dashboard = $this->dashboardAnalyticsService->payloadForUser($user);

        return view('tenant.dashboard.index', [
            'dashboard' => $dashboard,
            'lowCashBranches' => $dashboard['insights']['low_cash_branches'],
        ]);
    }
}
