<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Repositories\BranchRepository;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BranchRepository $branchRepository,
    ) {}

    public function index(): View
    {
        $lowCashBranches = $this->branchRepository->allWithCashbook()
            ->filter(static function (Branch $branch): bool {
                $cashbook = $branch->cashbook;
                $threshold = $branch->cashBalanceThreshold;

                if (! $cashbook || ! $threshold) {
                    return false;
                }

                $balance = (float) $cashbook->getAttribute('balance');
                $thresholdAmount = (float) $threshold->getAttribute('threshold_amount');

                return $balance < $thresholdAmount;
            })
            ->values();

        return view('tenant.dashboard.index', compact('lowCashBranches'));
    }
}
