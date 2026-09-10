<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Enums\Tenant\PaymentRequestType;
use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Services\SettingsService;
use App\Services\WorkflowEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentRequestResubmitController extends Controller
{
    public function __construct(
        private readonly WorkflowEngineService $engine,
        private readonly SettingsService $settingsService,
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

        if ($paymentRequest->type === PaymentRequestType::Expense->value
            && $this->settingsService->isExpenseSourceDocumentRequired()
            && $paymentRequest->attachments()->doesntExist()
        ) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.source_documents_required'));
        }

        $this->engine->resubmitAfterFix($paymentRequest, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.resubmitted'));
    }
}
