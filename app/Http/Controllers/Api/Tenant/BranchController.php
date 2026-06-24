<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Models\Tenant\Branch;
use App\Services\BranchScopeService;
use Illuminate\Http\JsonResponse;

class BranchController extends BaseApiController
{
    public function __construct(private readonly BranchScopeService $branchScope) {}

    public function __invoke(): JsonResponse
    {
        $user = $this->apiUser();
        $branchIds = $this->branchScope->allowedBranchIds($user);
        $branches = Branch::whereIn('id', $branchIds)->orderBy('name')->get(['id', 'name']);

        return response()->json(['data' => $branches]);
    }
}
