<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Http\Requests\Tenant\DisbursementStoreRequest;
use App\Models\Tenant\PaymentRequest;
use App\Services\BranchScopeService;
use App\Services\PaymentRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisbursementController extends BaseApiController
{
    public function __construct(
        private readonly PaymentRequestService $service,
        private readonly BranchScopeService $branchScope,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::DisburseRequests->value);

        $branchIds = $this->branchScope->allowedBranchIds($user);
        $perPage = min((int) $request->query('per_page', 20), 50);

        $paginator = PaymentRequest::query()
            ->where('status', 'approved')
            ->whereIn('branch_id', $branchIds)
            ->with(['staff', 'currency', 'branch'])
            ->latest()
            ->paginate($perPage);

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

    public function store(DisbursementStoreRequest $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::DisburseRequests->value);

        $branchIds = $this->branchScope->allowedBranchIds($user);
        abort_unless(in_array($paymentRequest->branch_id, $branchIds, true), 403);
        abort_unless($paymentRequest->status === 'approved', 422, 'Only approved requests can be disbursed.');

        $this->service->disburse($paymentRequest, $request->toDto(), $user);

        return response()->json(['data' => $paymentRequest->refresh()]);
    }
}
