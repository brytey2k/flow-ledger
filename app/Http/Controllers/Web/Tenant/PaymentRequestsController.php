<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PaymentRequestStoreRequest;
use App\Models\Tenant\AccountCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Repositories\PaymentRequestRepository;
use App\Services\PaymentRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

    public function create(Request $request): RedirectResponse|View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $staffProfile = $user->staffProfile()->with(['department', 'branch'])->first();

        if (! $staffProfile instanceof Staff || $staffProfile->branch_id === null) {
            return redirect()->route('payment-requests.index')
                ->with('error', 'Your account is not linked to a staff profile with a branch. Please contact an administrator.');
        }

        $currencies = Currency::orderBy('name')->get();
        $accountCodes = AccountCode::orderBy('code')->get();

        return view('tenant.payment-requests.create', compact('staffProfile', 'currencies', 'accountCodes'));
    }

    public function store(PaymentRequestStoreRequest $request): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        /** @var Staff $staffProfile */
        $staffProfile = $user->staffProfile;

        /** @var array{currency_id: int, type: string, notes: string|null, items: array<int, array{description: string, amount: float|string}>} $validated */
        $validated = $request->validated();

        $data = array_merge($validated, [
            'staff_id' => $staffProfile->id,
            'branch_id' => $staffProfile->branch_id,
        ]);

        /** @var array{staff_id: int, branch_id: int, currency_id: int, type: string, notes: string|null, items: array<int, array{description: string, amount: float|string}>} $data */
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
