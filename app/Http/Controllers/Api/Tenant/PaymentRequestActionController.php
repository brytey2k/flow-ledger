<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\BranchScopeService;
use App\Services\PaymentRequestService;
use App\Services\SettingsService;
use App\Services\WorkflowEngineService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class PaymentRequestActionController extends BaseApiController
{
    public function __construct(
        private readonly PaymentRequestService $service,
        private readonly WorkflowEngineService $engine,
        private readonly BranchScopeService $branchScope,
        private readonly SettingsService $settingsService,
    ) {}

    public function submit(PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);
        abort_unless($user->staffProfile?->id === $paymentRequest->staff_id, 403, 'You do not own this request.');
        abort_unless($paymentRequest->isDraft(), 422, 'Only draft requests can be submitted.');

        if ($paymentRequest->isExpense()
            && $this->settingsService->isExpenseSourceDocumentRequired()
            && $paymentRequest->attachments()->doesntExist()
        ) {
            return response()->json(['message' => 'Source documents are required for expense requests.'], 422);
        }

        try {
            $template = WorkflowTemplate::resolveForBranch($paymentRequest->type, $paymentRequest->branch_id);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'No workflow template is configured for this request type and branch.'], 422);
        }

        if (! $template->stages()->exists()) {
            return response()->json(['message' => 'No workflow template is configured for this request type and branch.'], 422);
        }

        $this->service->submit($paymentRequest, $user);

        return response()->json(['data' => $paymentRequest->refresh()]);
    }

    public function cancel(PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);
        abort_unless($user->staffProfile?->id === $paymentRequest->staff_id, 403, 'You do not own this request.');
        abort_unless($paymentRequest->isCancelable(), 422, 'This request cannot be cancelled in its current state.');

        $this->service->cancel($paymentRequest, $user);

        return response()->json(['data' => $paymentRequest->refresh()]);
    }

    public function resubmit(PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        abort_unless($paymentRequest->status === 'sent_back', 422, 'Only sent-back requests can be resubmitted.');
        abort_unless($user->staffProfile?->id === $paymentRequest->staff_id, 403, 'You do not own this request.');

        if ($paymentRequest->isExpense()
            && $this->settingsService->isExpenseSourceDocumentRequired()
            && $paymentRequest->attachments()->doesntExist()
        ) {
            return response()->json(['message' => 'Source documents are required for expense requests.'], 422);
        }

        $this->engine->resubmitAfterFix($paymentRequest, $user);

        return response()->json(['data' => $paymentRequest->refresh()]);
    }
}
