<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Services\WorkflowEngineService;
use Illuminate\Http\RedirectResponse;

class PaymentRequestResubmitController extends Controller
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
    ) {}

    public function store(PaymentRequest $paymentRequest): RedirectResponse
    {
        if ($paymentRequest->status !== 'sent_back') {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.resubmit_only_sent_back'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = auth()->user();

        $this->engine->resubmitAfterFix($paymentRequest, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.resubmitted'));
    }
}
