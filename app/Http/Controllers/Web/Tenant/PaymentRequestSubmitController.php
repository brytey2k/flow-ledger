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
                ->with('error', __('flash.requests.submit_only_draft'));
        }

        $template = WorkflowTemplate::where('type', $paymentRequest->type)->first();

        if ($template === null) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.missing_workflow_template'));
        }

        if (! $template->stages()->exists()) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.no_workflow_stages'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = auth()->user();
        $this->service->submit($paymentRequest, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.submitted'));
    }
}
