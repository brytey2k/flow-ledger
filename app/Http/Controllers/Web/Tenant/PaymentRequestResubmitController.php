<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Services\WorkflowEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentRequestResubmitController extends Controller
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
    ) {}

    public function store(Request $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        if ($paymentRequest->status !== 'sent_back') {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.resubmit_only_sent_back'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        if ($user->staffProfile?->id !== $paymentRequest->staff_id) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.resubmit_not_owner'));
        }

        $this->engine->resubmitAfterFix($paymentRequest, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.resubmitted'));
    }
}
