<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Tenant;

use App\Http\Requests\Tenant\CommentStoreRequest;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use Illuminate\Http\JsonResponse;

class CommentController extends BaseApiController
{
    public function storeForPaymentRequest(CommentStoreRequest $request, PaymentRequest $paymentRequest): JsonResponse
    {
        $user = $this->apiUser();
        $dto = $request->toDto();

        $comment = $paymentRequest->comments()->create([
            'user_id' => $user->id,
            'body' => $dto->body,
        ]);

        return response()->json(['data' => $comment->load('user')], 201);
    }

    public function storeForRetirementRequest(CommentStoreRequest $request, RetirementRequest $retirementRequest): JsonResponse
    {
        $user = $this->apiUser();
        $dto = $request->toDto();

        $comment = $retirementRequest->comments()->create([
            'user_id' => $user->id,
            'body' => $dto->body,
        ]);

        return response()->json(['data' => $comment->load('user')], 201);
    }
}
