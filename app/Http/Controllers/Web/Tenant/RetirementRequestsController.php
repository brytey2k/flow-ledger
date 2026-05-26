<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RetirementRequestStoreRequest;
use App\Http\Requests\Tenant\RetirementRequestUpdateRequest;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\CostCodeRepository;
use App\Repositories\RetirementRequestRepository;
use App\Services\BranchScopeService;
use App\Services\RetirementService;
use App\Services\WorkflowEngineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RetirementRequestsController extends Controller
{
    public function __construct(
        private readonly RetirementRequestRepository $repository,
        private readonly RetirementService $service,
        private readonly CostCodeRepository $costCodeRepository,
        private readonly WorkflowEngineService $workflowEngine,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $retirements = $this->repository->paginated($this->branchScope->allowedBranchIds($user));

        return view('tenant.retirement-requests.index', compact('retirements'));
    }

    public function create(PaymentRequest $paymentRequest, Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);
        abort_unless($paymentRequest->status === 'disbursed', 422, 'Can only retire disbursed advances.');
        // Disallow creating a new retirement if there is an active (non-cancelled) retirement
        abort_if($paymentRequest->hasActiveRetirement(), 422, 'This advance has already been retired.');

        $departmentId = $paymentRequest->staff?->department_id;
        $costCodes = $departmentId
            ? $this->costCodeRepository->forDepartment($departmentId)
            : $this->costCodeRepository->allOrderedByCode();

        $paymentRequest->load('items.costCode');

        return view('tenant.retirement-requests.create', compact('paymentRequest', 'costCodes'));
    }

    public function store(RetirementRequestStoreRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);
        abort_unless($paymentRequest->status === 'disbursed', 422, 'Can only retire disbursed advances.');
        // Disallow creating a new retirement if there is an active (non-cancelled) retirement
        abort_if($paymentRequest->hasActiveRetirement(), 422, 'This advance has already been retired.');

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $retirement = $this->service->createDraft($paymentRequest, $request->toDto(), $user);

        return redirect()->route('retirement-requests.show', $retirement)
            ->with('success', __('flash.retirements.draft_saved'));
    }

    public function show(RetirementRequest $retirementRequest, Request $request): View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($retirementRequest->paymentRequest?->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        $retirementRequest = $this->repository->findWithDetails($retirementRequest->id);

        $isOwner = $user->staffProfile?->id === $retirementRequest->paymentRequest?->staff_id;

        $activeInstanceStage = null;
        $canActOnActiveStage = false;

        /** @var \App\Models\Tenant\WorkflowInstance|null $activeInstance */
        $activeInstance = $retirementRequest->activeWorkflowInstance;
        if ($activeInstance !== null) {
            $activeInstanceStage = $activeInstance->instanceStages()
                ->where('status', 'active')
                ->get()
                ->first(fn(WorkflowInstanceStage $s) => $this->workflowEngine->canUserActOnStage($s, $user));

            $canActOnActiveStage = $activeInstanceStage !== null;
        }

        return view('tenant.retirement-requests.show', compact('retirementRequest', 'isOwner', 'activeInstanceStage', 'canActOnActiveStage'));
    }

    public function edit(RetirementRequest $retirementRequest, Request $request): RedirectResponse|View
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($retirementRequest->paymentRequest?->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        $retirementRequest->load('paymentRequest.currency', 'paymentRequest.staff.department', 'paymentRequest.branch', 'items.costCode');

        if (! $retirementRequest->isDraft() && ! $retirementRequest->isSentBack()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.edit_only_sent_back'));
        }

        if ($user->staffProfile?->id !== $retirementRequest->paymentRequest?->staff_id) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.edit_not_owner'));
        }

        $departmentId = $retirementRequest->paymentRequest?->staff?->department_id;
        $costCodes = $departmentId
            ? $this->costCodeRepository->forDepartment($departmentId)
            : $this->costCodeRepository->allOrderedByCode();

        return view('tenant.retirement-requests.edit', compact('retirementRequest', 'costCodes'));
    }

    public function update(RetirementRequestUpdateRequest $request, RetirementRequest $retirementRequest): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        abort_unless(in_array($retirementRequest->paymentRequest?->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        if (! $retirementRequest->isDraft() && ! $retirementRequest->isSentBack()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.edit_only_sent_back'));
        }

        if ($user->staffProfile?->id !== $retirementRequest->paymentRequest->staff_id) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.edit_not_owner'));
        }

        if ($retirementRequest->isDraft()) {
            $this->service->updateDraft($retirementRequest, $request->toDto(), $user);
        } else {
            $this->service->updateSentBack($retirementRequest, $request->toDto(), $user);
        }

        return redirect()->route('retirement-requests.show', $retirementRequest)
            ->with('success', __('flash.retirements.updated'));
    }
}
