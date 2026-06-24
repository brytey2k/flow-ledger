<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Http\Requests\Tenant\ManualReceiptStoreRequest;
use App\Repositories\CashbookRepository;
use App\Services\CashbookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CashbookController extends BaseApiController
{
    public function __construct(
        private readonly CashbookService $service,
        private readonly CashbookRepository $repository,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::AccessCashbook->value);

        abort_if(! $user->staffProfile()->exists(), 422, 'No staff profile found for your account.');

        $branchId = $user->operational_branch_id;

        $cashbook = $this->repository->findByBranchId($branchId);
        abort_if($cashbook === null, 404, 'No cashbook found for your branch.');

        $perPage = min((int) $request->query('per_page', 20), 50);
        $entries = $this->repository->paginatedEntriesForCashbook($cashbook, [], $perPage);

        return response()->json([
            'data' => [
                'cashbook' => $cashbook->load('currency'),
                'entries' => $entries->items(),
            ],
            'meta' => [
                'current_page' => $entries->currentPage(),
                'last_page' => $entries->lastPage(),
                'per_page' => $entries->perPage(),
                'total' => $entries->total(),
            ],
        ]);
    }

    public function storeEntry(ManualReceiptStoreRequest $request): JsonResponse
    {
        $user = $this->apiUser();
        $this->authorize(PermissionKey::CreateCashbookEntry->value);

        $branchId = $user->operational_branch_id;

        $cashbook = $this->repository->findByBranchId($branchId);
        abort_if($cashbook === null, 404, 'No cashbook found for your branch.');

        $entry = $this->service->recordManualReceipt($cashbook, $request->toDto(), $user);

        return response()->json(['data' => $entry], 201);
    }
}
