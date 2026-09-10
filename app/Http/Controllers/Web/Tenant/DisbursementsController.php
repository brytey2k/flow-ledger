<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Exceptions\InsufficientCashbookBalanceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DisbursementStoreRequest;
use App\Models\Tenant\PaymentRequest;
use App\Repositories\PaymentRequestRepository;
use App\Services\BranchScopeService;
use App\Services\PaymentRequestService;
use App\Services\ReportService;
use App\Services\WorkflowApproverResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisbursementsController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $service,
        private readonly PaymentRequestRepository $repository,
        private readonly BranchScopeService $branchScope,
        private readonly WorkflowApproverResolver $approvers,
        private readonly ReportService $reports,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $allowedBranchIds = $this->branchScope->allowedBranchIds($user);
        $requests = $this->repository->pendingDisbursement(
            $allowedBranchIds,
            user: $user,
        );
        $cashPositions = $this->reports->cashPosition(
            $allowedBranchIds,
            now()->toDateString(),
            now()->toDateString(),
        )['cashbooks'];

        return view('tenant.disbursements.index', compact('requests', 'cashPositions'));
    }

    public function store(DisbursementStoreRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        if ($paymentRequest->status !== 'approved') {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.disburse_only_approved'));
        }

        abort_unless($this->approvers->canDisburse($paymentRequest, $user), 403);

        try {
            $this->service->disburse($paymentRequest, $request->toDto(), $user);
        } catch (InsufficientCashbookBalanceException) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.insufficient_cashbook_balance'));
        }

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.disbursed'));
    }
}
