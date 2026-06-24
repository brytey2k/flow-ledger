<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Repositories\CurrencyRepository;
use Illuminate\Http\JsonResponse;

class CurrencyController extends BaseApiController
{
    public function __construct(private readonly CurrencyRepository $repository) {}

    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => $this->repository->allOrderedByName()]);
    }
}
