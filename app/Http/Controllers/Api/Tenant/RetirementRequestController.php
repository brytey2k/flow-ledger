<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Http\Requests\Tenant\RetirementRequestStoreRequest;
use App\Http\Requests\Tenant\RetirementRequestUpdateRequest;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Repositories\RetirementRequestRepository;
use App\Services\BranchScopeService;
use App\Services\RetirementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RetirementRequestController extends BaseApiController
{
    public function __construct(
        private readonly RetirementService $service,
        private readonly RetirementRequestRepository $repository,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::AccessRetirementRequests->value);

        $branchIds = $this->branchScope->allowedBranchIds($user);
        $perPage = min((int) $request->query('per_page', 20), 50);

        $paginator = $this->repository->paginated($branchIds, $perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function store(RetirementRequestStoreRequest $request): JsonResponse
    {
        $this->authorize(PermissionKey::CreateRetirementRequest->value);

        $paymentRequest = PaymentRequest::query()->findOrFail($request->integer('payment_request_id'));

        abort_unless($paymentRequest->status === 'disbursed', 422, 'Only disbursed advance requests can be retired.');
        abort_unless($paymentRequest->isAdvance(), 422, 'Only advance requests can have retirement requests.');

        $user = $this->apiUser();
        $retirement = $this->service->createDraft($paymentRequest, $request->toDto(), $user);

        return response()->json(['data' => $retirement->load('items', 'paymentRequest')], 201);
    }

    public function show(RetirementRequest $retirementRequest): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::AccessRetirementRequests->value);

        $branchIds = $this->branchScope->allowedBranchIds($user);
        $paymentBranchId = $retirementRequest->paymentRequest?->branch_id;
        abort_unless($paymentBranchId !== null && in_array($paymentBranchId, $branchIds, true), 403);

        $retirementRequest->load([
            'items',
            'paymentRequest.currency',
            'paymentRequest.staff',
            'activeWorkflowInstance.instanceStages.stage',
            'comments.user',
        ]);

        return response()->json(['data' => $retirementRequest]);
    }

    public function update(RetirementRequestUpdateRequest $request, RetirementRequest $retirementRequest): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::EditRetirementRequest->value);

        abort_unless(in_array($retirementRequest->status, ['draft', 'sent_back'], true), 422, 'Only draft or sent-back retirements can be edited.');

        $updated = $retirementRequest->isDraft()
            ? $this->service->updateDraft($retirementRequest, $request->toDto(), $user)
            : $this->service->updateDraft($retirementRequest, $request->toDto(), $user);

        return response()->json(['data' => $updated->load('items', 'paymentRequest')]);
    }
}
