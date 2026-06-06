<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Enums\Tenant\PaymentRequestType;
use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;

class PaymentRequestSubmitController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $service,
        private readonly SettingsService $settingsService,
    ) {}

    public function store(PaymentRequest $paymentRequest): RedirectResponse
    {
        if (! $paymentRequest->isDraft()) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.submit_only_draft'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = auth()->user();

        if ($paymentRequest->type === PaymentRequestType::Expense->value) {
            if ($this->settingsService->isExpenseSourceDocumentRequired() && $paymentRequest->attachments()->doesntExist()) {
                return redirect()->route('payment-requests.show', $paymentRequest)
                    ->with('error', __('flash.requests.source_documents_required'));
            }

            $this->service->submit($paymentRequest, $user);

            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('success', __('flash.requests.submitted'));
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

        $this->service->submit($paymentRequest, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.submitted'));
    }
}
