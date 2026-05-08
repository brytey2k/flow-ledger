<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PaymentRequestStoreRequest;
use App\Models\Tenant\AccountCode;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Repositories\PaymentRequestRepository;
use App\Services\PaymentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentRequestsController extends Controller
{
    public function __construct(
        private readonly PaymentRequestRepository $repository,
        private readonly PaymentRequestService $service,
    ) {}

    public function index(): View
    {
        $requests = $this->repository->paginated();

        return view('tenant.payment-requests.index', compact('requests'));
    }

    public function create(): View
    {
        $staff = Staff::orderBy('first_name')->get();
        $branches = Branch::orderBy('name')->get();
        $currencies = Currency::orderBy('name')->get();
        $accountCodes = AccountCode::orderBy('code')->get();

        return view('tenant.payment-requests.create', compact('staff', 'branches', 'currencies', 'accountCodes'));
    }

    public function store(PaymentRequestStoreRequest $request): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        /** @var array{staff_id: int, branch_id: int, currency_id: int, type: string, notes: string|null, items: array<int, array{description: string, amount: float|string}>} $data */
        $data = $request->validated();
        $paymentRequest = $this->service->createDraft($data, $user);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', 'Request saved as draft.');
    }

    public function show(PaymentRequest $paymentRequest): View
    {
        $paymentRequest = $this->repository->findWithDetails($paymentRequest->id);

        return view('tenant.payment-requests.show', compact('paymentRequest'));
    }

    public function destroy(PaymentRequest $paymentRequest): RedirectResponse
    {
        if (! $paymentRequest->isDraft()) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', 'Only draft requests can be deleted.');
        }

        $paymentRequest->delete();

        return redirect()->route('payment-requests.index')
            ->with('success', 'Request deleted.');
    }
}
