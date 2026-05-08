<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RetirementRequestStoreRequest;
use App\Models\Tenant\AccountCode;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Repositories\RetirementRequestRepository;
use App\Services\RetirementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RetirementRequestsController extends Controller
{
    public function __construct(
        private readonly RetirementRequestRepository $repository,
        private readonly RetirementService $service,
    ) {}

    public function index(): View
    {
        $retirements = $this->repository->paginated();

        return view('tenant.retirement-requests.index', compact('retirements'));
    }

    public function create(PaymentRequest $paymentRequest): View
    {
        abort_unless($paymentRequest->status === 'disbursed', 422, 'Can only retire disbursed advances.');
        abort_if($paymentRequest->retirementRequest()->exists(), 422, 'This advance has already been retired.');

        $accountCodes = AccountCode::orderBy('code')->get();

        return view('tenant.retirement-requests.create', compact('paymentRequest', 'accountCodes'));
    }

    public function store(RetirementRequestStoreRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        abort_unless($paymentRequest->status === 'disbursed', 422, 'Can only retire disbursed advances.');
        abort_if($paymentRequest->retirementRequest()->exists(), 422, 'This advance has already been retired.');

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        /** @var array{notes: string|null, items: array<int, array{description: string, amount: float|string, account_code_id: int, receipt_number: string|null}>} $data */
        $data = $request->validated();
        $retirement = $this->service->createDraft($paymentRequest, $data, $user);

        return redirect()->route('retirement-requests.show', $retirement)
            ->with('success', 'Retirement saved as draft.');
    }

    public function show(RetirementRequest $retirementRequest): View
    {
        $retirementRequest = $this->repository->findWithDetails($retirementRequest->id);

        return view('tenant.retirement-requests.show', compact('retirementRequest'));
    }
}
