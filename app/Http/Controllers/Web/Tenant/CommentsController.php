<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CommentStoreRequest;
use App\Models\Tenant\Comment;
use App\Models\Tenant\PaymentRequest;
use Illuminate\Http\RedirectResponse;

class CommentsController extends Controller
{
    public function store(CommentStoreRequest $request, PaymentRequest $paymentRequest): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = $request->user();

        $dto = $request->toDto();

        $paymentRequest->comments()->create([
            'user_id' => $user->id,
            'body' => $dto->body,
        ]);

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.comments.added'));
    }

    public function destroy(PaymentRequest $paymentRequest, Comment $comment): RedirectResponse
    {
        /** @var \App\Models\Tenant\User $user */
        $user = auth()->user();

        abort_unless($comment->user_id === $user->id, 403);

        $comment->delete();

        return redirect()->route('payment-requests.show', $paymentRequest)
            ->with('success', __('flash.comments.deleted'));
    }
}
