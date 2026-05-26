<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Services\BranchScopeService;
use App\Services\PaymentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentRequestCancelController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $service,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function store(PaymentRequest $paymentRequest, Request $request): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        abort_unless(
            in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true),
            403,
        );

        if ($user->staffProfile?->id !== $paymentRequest->staff_id) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.cancel_not_owner'));
        }

        if (! $paymentRequest->isCancelable()) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.cannot_cancel_status'));
        }

        $this->service->cancel($paymentRequest, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.cancelled'));
    }
}
