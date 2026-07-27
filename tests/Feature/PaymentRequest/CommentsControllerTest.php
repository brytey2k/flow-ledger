<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Enums\Tenant\PermissionKey;
use App\Models\Role;
use App\Models\Tenant\Comment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowEngineService;
use Tests\TenantAppTestCase;

class CommentsControllerTest extends TenantAppTestCase
{
    private function ownedPaymentRequest(): PaymentRequest
    {
        $staff = Staff::factory()->withUser($this->user)->create(['branch_id' => $this->branch->id]);

        return PaymentRequest::factory()->advance()->create([
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);
    }

    /** @return array{template: WorkflowTemplate, stage: WorkflowStage, role: Role} */
    private function makeApprovalStage(int $displayOrder = 1): array
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
        $role->givePermissionTo(PermissionKey::AccessPaymentRequests->value);
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => $displayOrder,
        ]);
        $stage->roles()->sync([$role->id]);

        return ['template' => $template, 'stage' => $stage->fresh(), 'role' => $role];
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_cannot_post_comment(): void
    {
        $paymentRequest = $this->ownedPaymentRequest();

        $response = $this->post(route('payment-requests.comments.store', $paymentRequest), [
            'body' => 'Hello',
        ]);

        $response->assertRedirect(route('login'));
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_post_comment(): void
    {
        $paymentRequest = $this->ownedPaymentRequest();

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
        $paymentRequest = $this->ownedPaymentRequest();

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.comments.store', $paymentRequest),
            ['body' => ''],
        );

        $response->assertSessionHasErrors(['body']);
    }

    public function test_comment_body_max_length_is_enforced(): void
    {
        $paymentRequest = $this->ownedPaymentRequest();

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.comments.store', $paymentRequest),
            ['body' => str_repeat('a', 2001)],
        );

        $response->assertSessionHasErrors(['body']);
    }

    public function test_user_outside_branch_cannot_post_comment(): void
    {
        $paymentRequest = $this->ownedPaymentRequest();
        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider)->post(
            route('payment-requests.comments.store', $paymentRequest),
            ['body' => 'Sneaky comment.'],
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('comments', [
            'commentable_type' => PaymentRequest::class,
            'commentable_id' => $paymentRequest->id,
            'body' => 'Sneaky comment.',
        ]);
    }

    public function test_in_branch_user_not_in_approval_chain_cannot_post_comment(): void
    {
        $paymentRequest = $this->ownedPaymentRequest();
        $bystander = User::factory()->create(['branch_id' => $this->branch->id, 'operational_branch_id' => $this->branch->id]);
        $bystander->assignRole($this->role);

        $response = $this->actingAs($bystander)->post(
            route('payment-requests.comments.store', $paymentRequest),
            ['body' => 'Not my business.'],
        );

        $response->assertForbidden();
    }

    public function test_approver_on_active_stage_can_post_comment(): void
    {
        $engine = app(WorkflowEngineService::class);
        $paymentRequest = $this->ownedPaymentRequest();

        ['template' => $template, 'role' => $role] = $this->makeApprovalStage();
        $approver = User::factory()->create(['branch_id' => $this->branch->id, 'operational_branch_id' => $this->branch->id]);
        $approver->assignRole($role);

        $engine->startWorkflow($paymentRequest, $template, $this->user);

        $response = $this->actingAs($approver)->post(
            route('payment-requests.comments.store', $paymentRequest),
            ['body' => 'Please clarify the amount.'],
        );

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $this->assertDatabaseHas('comments', [
            'commentable_type' => PaymentRequest::class,
            'commentable_id' => $paymentRequest->id,
            'user_id' => $approver->id,
            'body' => 'Please clarify the amount.',
        ]);
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
        $otherUser = User::factory()->create();
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
