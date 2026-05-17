<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\PaymentRequestStoreRequest;
use App\Http\Requests\Tenant\PaymentRequestUpdateRequest;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\CostCodeRepository;
use App\Repositories\CurrencyRepository;
use App\Repositories\PaymentRequestRepository;
use App\Services\BranchScopeService;
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
        private readonly CostCodeRepository $costCodeRepository,
        private readonly WorkflowEngineService $workflowEngine,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $requests = $this->repository->paginated($this->branchScope->allowedBranchIds($user));

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
        $costCodes = $this->costCodeRepository->forDepartment($staffProfile->department_id);

        return view('tenant.payment-requests.create', compact('staffProfile', 'currencies', 'costCodes'));
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
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        $paymentRequest = $this->repository->findWithDetails($paymentRequest->id);

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
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        if ($paymentRequest->status !== 'sent_back') {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.edit_only_sent_back'));
        }

        if ($user->staffProfile?->id !== $paymentRequest->staff_id) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.edit_not_owner'));
        }

        $paymentRequest->load('items.costCode', 'currency', 'staff.department', 'staff.branch');

        $currencies = $this->currencyRepository->allOrderedByName();
        $departmentId = $paymentRequest->staff?->department_id;
        $costCodes = $departmentId !== null
            ? $this->costCodeRepository->forDepartment((int) $departmentId)
            : $this->costCodeRepository->allOrderedByCode();

        return view('tenant.payment-requests.edit', compact('paymentRequest', 'currencies', 'costCodes'));
    }

    public function update(PaymentRequestUpdateRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        if ($paymentRequest->status !== 'sent_back') {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.edit_only_sent_back'));
        }

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

    public function destroy(PaymentRequest $paymentRequest, Request $request): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        if (! $paymentRequest->isDraft()) {
            return redirect()->route('payment-requests.show', $paymentRequest)
                ->with('error', __('flash.requests.draft_delete_only'));
        }

        $paymentRequest->delete();

        return redirect()->route('payment-requests.index')
            ->with('success', __('flash.requests.deleted'));
    }
}
