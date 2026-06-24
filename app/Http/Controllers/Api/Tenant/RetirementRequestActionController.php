<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Models\Tenant\RetirementRequest;
use App\Services\RetirementService;
use App\Services\WorkflowEngineService;
use Illuminate\Http\JsonResponse;

class RetirementRequestActionController extends BaseApiController
{
    public function __construct(
        private readonly RetirementService $service,
        private readonly WorkflowEngineService $engine,
    ) {}

    public function submit(RetirementRequest $retirementRequest): JsonResponse
    {
        $user = $this->apiUser();
        abort_unless($retirementRequest->status === 'draft', 422, 'Only draft retirements can be submitted.');
        abort_unless($retirementRequest->paymentRequest?->staff?->user_id === $user->id, 403, 'You do not own this request.');

        $this->service->submit($retirementRequest, $user);

        return response()->json(['data' => $retirementRequest->refresh()]);
    }

    public function cancel(RetirementRequest $retirementRequest): JsonResponse
    {
        $user = $this->apiUser();
        abort_unless(in_array($retirementRequest->status, ['draft', 'sent_back'], true), 422, 'This retirement cannot be cancelled in its current state.');
        abort_unless($retirementRequest->paymentRequest?->staff?->user_id === $user->id, 403, 'You do not own this request.');

        $this->service->cancel($retirementRequest, $user);

        return response()->json(['data' => $retirementRequest->refresh()]);
    }

    public function resubmit(RetirementRequest $retirementRequest): JsonResponse
    {
        $user = $this->apiUser();
        abort_unless($retirementRequest->status === 'sent_back', 422, 'Only sent-back retirements can be resubmitted.');
        abort_unless($retirementRequest->paymentRequest?->staff?->user_id === $user->id, 403, 'You do not own this request.');

        $this->engine->resubmitAfterFix($retirementRequest, $user);

        return response()->json(['data' => $retirementRequest->refresh()]);
    }
}
