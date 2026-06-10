<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CashCountStoreRequest;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Repositories\CashCountRepository;
use App\Repositories\CurrencyDenominationRepository;
use App\Services\BranchScopeService;
use App\Services\CashCountService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CashCountController extends Controller
{
    public function __construct(
        private readonly CashCountRepository $repository,
        private readonly CurrencyDenominationRepository $denominationRepository,
        private readonly CashCountService $service,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Branch $branch, Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($user), true), 403);

        $cashbook = $this->getCashbook($branch);
        $cashCounts = $this->repository->paginatedForCashbook($cashbook);

        return view('tenant.cash-count.index', compact('branch', 'cashbook', 'cashCounts'));
    }

    public function create(Branch $branch, Request $request): RedirectResponse|View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($user), true), 403);

        $cashbook = $this->getCashbook($branch);
        $currency = $cashbook->currency;

        $denominations = $this->denominationRepository->allForCurrency($currency);

        if ($denominations->isEmpty()) {
            return redirect()
                ->route('cashbook.index', $branch)
                ->with('warning', __('flash.cash_count.no_denominations'));
        }

        return view('tenant.cash-count.create', compact('branch', 'cashbook', 'denominations'));
    }

    public function store(CashCountStoreRequest $request, Branch $branch): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($user), true), 403);

        $cashbook = $this->getCashbook($branch);
        $cashCount = $this->service->store($cashbook, $request->toDto(), $user);

        return redirect()
            ->route('cash-count.show', [$branch, $cashCount])
            ->with('success', __('flash.cash_count.created'));
    }

    public function show(Branch $branch, CashCount $cashCount, Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($user), true), 403);

        $cashbook = $this->getCashbook($branch);
        abort_unless($cashCount->cashbook_id === $cashbook->id, 403);

        $cashCount->load(['countedBy', 'items.denomination']);

        return view('tenant.cash-count.show', compact('branch', 'cashbook', 'cashCount'));
    }

    public function destroy(Branch $branch, CashCount $cashCount, Request $request): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($branch->id, $this->branchScope->allowedBranchIds($user), true), 403);

        $cashbook = $this->getCashbook($branch);
        abort_unless($cashCount->cashbook_id === $cashbook->id, 403);

        $this->service->delete($cashCount, $user);

        return redirect()
            ->route('cash-count.index', $branch)
            ->with('success', __('flash.cash_count.deleted'));
    }

    private function getCashbook(Branch $branch): Cashbook
    {
        return Cashbook::firstOrCreate(
            ['branch_id' => $branch->id],
            ['currency_id' => $branch->currency_id, 'balance' => 0],
        );
    }
}
