<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\DisbursementStoreRequest;
use App\Models\Tenant\PaymentRequest;
use App\Repositories\PaymentRequestRepository;
use App\Services\PaymentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DisbursementsController extends Controller
{
    public function __construct(
        private readonly PaymentRequestService $service,
        private readonly PaymentRequestRepository $repository,
    ) {}

    public function index(): View
    {
        $requests = $this->repository->pendingDisbursement();

        return view('tenant.disbursements.index', compact('requests'));
    }

    public function store(DisbursementStoreRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        if ($paymentRequest->status !== 'approved') {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', 'Only approved requests can be disbursed.');
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        $this->service->disburse($paymentRequest, $request->toDto(), $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', 'Request marked as disbursed.');
    }
}
