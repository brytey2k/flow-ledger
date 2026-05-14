<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PaymentRequestStoreRequest;
use App\Http\Requests\Tenant\PaymentRequestUpdateRequest;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\AccountCodeRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\PaymentRequestRepository;
use App\Services\PaymentRequestService;
use App\Services\WorkflowEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PaymentRequestsController extends Controller
{
    public function __construct(
        private readonly PaymentRequestRepository $repository,
        private readonly PaymentRequestService $service,
        private readonly CurrencyRepository $currencyRepository,
        private readonly AccountCodeRepository $accountCodeRepository,
        private readonly WorkflowEngineService $workflowEngine,
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
                ->with('error', __('flash.requests.missing_staff_profile'));
        }

        $currencies = $this->currencyRepository->allOrderedByName();
        $accountCodes = $this->accountCodeRepository->allOrderedByCode();

        return view('tenant.payment-requests.create', compact('staffProfile', 'currencies', 'accountCodes'));
    }

    public function store(PaymentRequestStoreRequest $request): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        /** @var Staff $staffProfile */
        $staffProfile = $user->staffProfile;

        $paymentRequest = $this->service->createDraft(
            $request->toDto($staffProfile->id, (int) $staffProfile->branch_id),
            $user,
        );

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.draft_saved'));
    }

    public function show(PaymentRequest $paymentRequest, Request $request): View
    {
        $paymentRequest = $this->repository->findWithDetails($paymentRequest->id);

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $activeInstanceStage = null;
        $canActOnActiveStage = false;

        /** @var \App\Models\Tenant\WorkflowInstance|null $activeInstance */
        $activeInstance = $paymentRequest->activeWorkflowInstance;
        if ($activeInstance !== null) {
            $activeInstanceStage = $activeInstance->instanceStages()
                ->where('status', 'active')
                ->get()
                ->first(fn(WorkflowInstanceStage $s) => $this->workflowEngine->canUserActOnStage($s, $user));

            $canActOnActiveStage = $activeInstanceStage !== null;
        }

        $isOwner = $user->staffProfile?->id === $paymentRequest->staff_id;

        return view('tenant.payment-requests.show', compact('paymentRequest', 'activeInstanceStage', 'canActOnActiveStage', 'isOwner'));
    }

    public function edit(PaymentRequest $paymentRequest, Request $request): RedirectResponse|View
    {
        if ($paymentRequest->status !== 'sent_back') {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.edit_only_sent_back'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        if ($user->staffProfile?->id !== $paymentRequest->staff_id) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.edit_not_owner'));
        }

        $paymentRequest->load('items.accountCode', 'currency', 'staff.department', 'staff.branch');

        $currencies = $this->currencyRepository->allOrderedByName();
        $accountCodes = $this->accountCodeRepository->allOrderedByCode();

        return view('tenant.payment-requests.edit', compact('paymentRequest', 'currencies', 'accountCodes'));
    }

    public function update(PaymentRequestUpdateRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        if ($paymentRequest->status !== 'sent_back') {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.edit_only_sent_back'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        if ($user->staffProfile?->id !== $paymentRequest->staff_id) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.edit_not_owner'));
        }

        /** @var Staff $staffProfile */
        $staffProfile = $user->staffProfile;

        $this->service->updateSentBack(
            $paymentRequest,
            $request->toDto($staffProfile->id, (int) $staffProfile->branch_id, $paymentRequest->type),
            $user,
        );

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.requests.updated'));
    }

    public function destroy(PaymentRequest $paymentRequest): RedirectResponse
    {
        if (! $paymentRequest->isDraft()) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.draft_delete_only'));
        }

        $paymentRequest->delete();

        return redirect()->route('payment-requests.index')
            ->with('success', __('flash.requests.deleted'));
    }
}
