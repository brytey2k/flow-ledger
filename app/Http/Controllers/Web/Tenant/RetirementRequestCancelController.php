<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\RetirementRequest;
use App\Services\BranchScopeService;
use App\Services\RetirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class RetirementRequestCancelController extends Controller
{
    public function __construct(
        private readonly RetirementService $service,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function store(RetirementRequest $retirementRequest, Request $request): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        $paymentRequest = $retirementRequest->paymentRequest;

        abort_unless(
            $paymentRequest !== null && in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true),
            403,
        );

        if ($user->staffProfile?->id !== $paymentRequest->staff_id) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.requests.cancel_not_owner'));
        }

        if (! $retirementRequest->isCancelable()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.requests.cannot_cancel_status'));
        }

        $this->service->cancel($retirementRequest, $user);

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', __('flash.requests.cancelled'));
    }
}
