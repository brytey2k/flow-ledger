<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Branch;
use App\Repositories\BranchRepository;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(
        private readonly BranchRepository $branchRepository,
    ) {}

    public function index(): View
    {
        $lowCashBranches = $this->getLowCashBranches();

        return view('tenant.dashboard.index', compact('lowCashBranches'));
    }

    /** @return Collection<int, Branch> */
    private function getLowCashBranches(): Collection
    {
        if (! auth()->user()?->can(PermissionKey::AccessSettings->value)) {
            /** @var Collection<int, Branch> $empty */
            $empty = collect();

            return $empty;
        }

        /** @var Collection<int, Branch> $result */
        $result = $this->branchRepository->allWithCashbook()
            ->filter(static function (Branch $branch): bool {
                $cashbook = $branch->cashbook;
                $threshold = $branch->cashBalanceThreshold;

                if (! $cashbook || ! $threshold) {
                    return false;
                }

                /** @var float $balance */
                $balance = $cashbook->getAttribute('balance');
                /** @var float $thresholdAmount */
                $thresholdAmount = $threshold->getAttribute('threshold_amount');

                return $balance < $thresholdAmount;
            })
            ->values();

        return $result;
    }
}
