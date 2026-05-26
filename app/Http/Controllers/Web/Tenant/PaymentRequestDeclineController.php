<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Services\BranchScopeService;
use App\Services\PaymentRequestService;
use App\Services\WorkflowEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PaymentRequestDeclineController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $service,
        private readonly WorkflowEngineService $workflowEngine,
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

        $activeInstance = $paymentRequest->activeWorkflowInstance;

        if ($activeInstance === null) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.no_active_workflow'));
        }

        $activeInstanceStage = $activeInstance->instanceStages()
            ->where('status', 'active')
            ->get()
            ->first(fn(WorkflowInstanceStage $s) => $this->workflowEngine->canUserActOnStage($s, $user));

        if ($activeInstanceStage === null) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.cannot_decline'));
        }

        $this->service->decline($paymentRequest, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.declined'));
    }
}
