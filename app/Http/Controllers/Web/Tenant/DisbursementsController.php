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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DisbursementsController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $service,
        private readonly PaymentRequestRepository $repository,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $requests = $this->repository->pendingDisbursement($this->branchScope->allowedBranchIds($user));

        return view('tenant.disbursements.index', compact('requests'));
    }

    public function store(DisbursementStoreRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        if ($paymentRequest->status !== 'approved') {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.disburse_only_approved'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        try {
            $this->service->disburse($paymentRequest, $request->toDto(), $user);
        } catch (InsufficientCashbookBalanceException $e) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', $e->getMessage());
        }

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.disbursed'));
    }
}
