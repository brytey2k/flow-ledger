<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Exceptions\InsufficientCashbookBalanceException;
use App\Http\Controllers\Controller;
use App\Models\Tenant\RetirementRequest;
use App\Services\BranchScopeService;
use App\Services\RetirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RetirementSettlementController extends Controller
{
    public function __construct(
        private readonly RetirementService $service,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function store(Request $request, RetirementRequest $retirementRequest): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        $paymentRequest = $retirementRequest->paymentRequest;
        abort_unless(
            $paymentRequest !== null && in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true),
            403,
        );

        if ($retirementRequest->status !== 'approved') {
            return redirect()->back()->with('error', __('flash.retirements.settle_only_approved'));
        }

        $request->validate([
            'settlement_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->service->settle(
                $retirementRequest,
                $request->string('settlement_notes')->toString() ?: null,
                $user,
            );
        } catch (InsufficientCashbookBalanceException) {
            return redirect()->back()->with('error', __('flash.retirements.insufficient_cashbook_balance'));
        }

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', __('flash.retirements.settled'));
    }
}
