<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use Illuminate\Http\RedirectResponse;

class PaymentRequestSubmitController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $service,
    ) {}

    public function store(PaymentRequest $paymentRequest): RedirectResponse
    {
        if (! $paymentRequest->isDraft()) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', 'Only draft requests can be submitted.');
        }

        if (! WorkflowTemplate::where('type', $paymentRequest->type)->exists()) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', 'No workflow template configured for this request type. Please ask an administrator to set one up.');
        }

        /** @var \App\Models\Tenant\User $user */
        $user = auth()->user();
        $this->service->submit($paymentRequest, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', 'Request submitted for approval.');
    }
}
