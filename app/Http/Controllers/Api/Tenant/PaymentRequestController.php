<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Http\Requests\Tenant\PaymentRequestStoreRequest;
use App\Http\Requests\Tenant\PaymentRequestUpdateRequest;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Repositories\PaymentRequestRepository;
use App\Services\BranchScopeService;
use App\Services\PaymentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentRequestController extends BaseApiController
{
    public function __construct(
        private readonly PaymentRequestRepository $repository,
        private readonly PaymentRequestService $service,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::AccessPaymentRequests->value);

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

    public function store(PaymentRequestStoreRequest $request): JsonResponse
    {
        $this->authorize(PermissionKey::CreatePaymentRequest->value);

        $user = $this->apiUser();

        /** @var Staff $staffProfile */
        $staffProfile = $user->staffProfile;

        $paymentRequest = $this->service->createDraft(
            $request->toDto($staffProfile->id, (int) $staffProfile->branch_id),
            $user,
        );

        return response()->json(['data' => $paymentRequest->load('items', 'branch', 'currency', 'staff')], 201);
    }

    public function show(PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::AccessPaymentRequests->value);

        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);

        $paymentRequest = $this->repository->findWithDetails($paymentRequest->id);

        return response()->json(['data' => $paymentRequest]);
    }

    public function update(PaymentRequestUpdateRequest $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::EditPaymentRequest->value);

        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);
        abort_unless(in_array($paymentRequest->status, ['draft', 'sent_back'], true), 422, 'Only draft or sent-back requests can be edited.');
        abort_unless($user->staffProfile?->id === $paymentRequest->staff_id, 403, 'You do not own this request.');

        /** @var Staff $staffProfile */
        $staffProfile = $user->staffProfile;
        $dto = $request->toDto($staffProfile->id, (int) $staffProfile->branch_id, $paymentRequest->type);

        $updated = $paymentRequest->isDraft()
            ? $this->service->updateDraft($paymentRequest, $dto, $user)
            : $this->service->updateSentBack($paymentRequest, $dto, $user);

        return response()->json(['data' => $updated->load('items', 'branch', 'currency', 'staff')]);
    }

    public function destroy(PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::DeletePaymentRequest->value);

        abort_unless(in_array($paymentRequest->branch_id, $this->branchScope->allowedBranchIds($user), true), 403);
        abort_unless($paymentRequest->isDraft(), 422, 'Only draft requests can be deleted.');

        $paymentRequest->delete();

        return response()->json(null, 204);
    }
}
