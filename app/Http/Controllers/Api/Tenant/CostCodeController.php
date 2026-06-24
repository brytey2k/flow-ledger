<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Repositories\CostCodeRepository;
use Illuminate\Http\JsonResponse;

class CostCodeController extends BaseApiController
{
    public function __construct(private readonly CostCodeRepository $repository) {}

    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => $this->repository->allOrderedByCode()]);
    }
}
