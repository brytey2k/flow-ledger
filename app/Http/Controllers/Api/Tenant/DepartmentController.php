<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Repositories\DepartmentRepository;
use Illuminate\Http\JsonResponse;

class DepartmentController extends BaseApiController
{
    public function __construct(private readonly DepartmentRepository $repository) {}

    public function __invoke(): JsonResponse
    {
        return response()->json(['data' => $this->repository->allOrderedByName()]);
    }
}
