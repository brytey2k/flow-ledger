<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Models\Tenant\Comment;
use App\Models\Tenant\PaymentRequest;
use Tests\TenantAppTestCase;

class CommentsControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_cannot_post_comment(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();

        $response = $this->post(route('payment-requests.comments.store', $paymentRequest), [
            'body' => 'Hello',
        ]);

        $response->assertRedirect(route('login'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_post_comment(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.comments.store', $paymentRequest),
            ['body' => 'This looks good.'],
        );

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('comments', [
            'commentable_type' => PaymentRequest::class,
            'commentable_id' => $paymentRequest->id,
            'user_id' => $this->user->id,
            'body' => 'This looks good.',
        ]);
    }

    public function test_comment_body_is_required(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.comments.store', $paymentRequest),
            ['body' => ''],
        );

        $response->assertSessionHasErrors(['body']);
    }

    public function test_comment_body_max_length_is_enforced(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.comments.store', $paymentRequest),
            ['body' => str_repeat('a', 2001)],
        );

        $response->assertSessionHasErrors(['body']);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_user_can_delete_own_comment(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $comment = Comment::factory()->create([
            'commentable_type' => PaymentRequest::class,
            'commentable_id' => $paymentRequest->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)->delete(
            route('payment-requests.comments.destroy', [$paymentRequest, $comment]),
        );

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    public function test_user_cannot_delete_another_users_comment(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $otherUser = \App\Models\Tenant\User::factory()->create();
        $comment = Comment::factory()->create([
            'commentable_type' => PaymentRequest::class,
            'commentable_id' => $paymentRequest->id,
            'user_id' => $otherUser->id,
        ]);

        $response = $this->actingAs($this->user)->delete(
            route('payment-requests.comments.destroy', [$paymentRequest, $comment]),
        );

        $response->assertForbidden();
        $this->assertModelExists($comment);
    }
}
