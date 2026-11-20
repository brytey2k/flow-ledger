<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Enums\Tenant\PaymentRequestType;
use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\BranchScopeService;
use App\Services\PaymentRequestService;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;

class PaymentRequestSubmitController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $service,
        private readonly SettingsService $settingsService,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function store(PaymentRequest $paymentRequest): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = auth()->user();

        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        if ($user->staffProfile?->id !== $paymentRequest->staff_id) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.edit_not_owner'));
        }

        if (! $paymentRequest->isDraft()) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.submit_only_draft'));
        }

        if ($paymentRequest->type === PaymentRequestType::Expense->value
            && $this->settingsService->isExpenseSourceDocumentRequired()
            && $paymentRequest->attachments()->doesntExist()
        ) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.source_documents_required'));
        }

        // Resolve the template exactly as PaymentRequestService::submit() will: the branch's current version
        // first, then the master's. A draft or superseded version never counts.
        try {
            $template = WorkflowTemplate::resolveForBranch($paymentRequest->type, $paymentRequest->branch_id);
        } catch (ModelNotFoundException) {
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
