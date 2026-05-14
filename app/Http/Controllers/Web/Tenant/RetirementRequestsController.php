<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\RetirementRequestStoreRequest;
use App\Http\Requests\Tenant\RetirementRequestUpdateRequest;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Repositories\AccountCodeRepository;
use App\Repositories\RetirementRequestRepository;
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
        private readonly AccountCodeRepository $accountCodeRepository,
        private readonly WorkflowEngineService $workflowEngine,
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

        $departmentId = $paymentRequest->staff?->department_id;
        $accountCodes = $departmentId
            ? $this->accountCodeRepository->forDepartment($departmentId)
            : $this->accountCodeRepository->allOrderedByCode();

        return view('tenant.retirement-requests.create', compact('paymentRequest', 'accountCodes'));
    }

    public function store(RetirementRequestStoreRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        abort_unless($paymentRequest->status === 'disbursed', 422, 'Can only retire disbursed advances.');
        abort_if($paymentRequest->retirementRequest()->exists(), 422, 'This advance has already been retired.');

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
        $retirement = $this->service->createDraft($paymentRequest, $request->toDto(), $user);

        return redirect()->route('retirement-requests.show', $retirement)
            ->with('success', __('flash.retirements.draft_saved'));
    }

    public function show(RetirementRequest $retirementRequest, Request $request): View
    {
        $retirementRequest = $this->repository->findWithDetails($retirementRequest->id);

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();
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
        $retirementRequest->load('paymentRequest.currency', 'paymentRequest.staff.department', 'paymentRequest.branch', 'items.accountCode');

        if (! $retirementRequest->isDraft() && ! $retirementRequest->isSentBack()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.edit_only_sent_back'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        if ($user->staffProfile?->id !== $retirementRequest->paymentRequest?->staff_id) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.edit_not_owner'));
        }

        $departmentId = $retirementRequest->paymentRequest?->staff?->department_id;
        $accountCodes = $departmentId
            ? $this->accountCodeRepository->forDepartment($departmentId)
            : $this->accountCodeRepository->allOrderedByCode();

        return view('tenant.retirement-requests.edit', compact('retirementRequest', 'accountCodes'));
    }

    public function update(RetirementRequestUpdateRequest $request, RetirementRequest $retirementRequest): RedirectResponse
    {
        if (! $retirementRequest->isDraft() && ! $retirementRequest->isSentBack()) {
            return redirect()->route('retirement-requests.show', $retirementRequest)
                ->with('error', __('flash.retirements.edit_only_sent_back'));
        }

        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        if ($user->staffProfile?->id !== $retirementRequest->paymentRequest?->staff_id) {
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
