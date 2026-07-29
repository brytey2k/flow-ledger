<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Role;
use App\Models\Tenant\Comment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowEngineService;

function ownedPaymentRequest(): PaymentRequest
{
    $staff = Staff::factory()->withUser(test()->user)->create(['branch_id' => test()->branch->id]);

    return PaymentRequest::factory()->advance()->create([
        'staff_id' => $staff->id,
        'branch_id' => test()->branch->id,
    ]);
}
/** @return array{template: WorkflowTemplate, stage: WorkflowStage, role: Role} */
function makeApprovalStageForCommentsController(int $displayOrder = 1): array
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
test('guest cannot post comment', function () {
    $paymentRequest = ownedPaymentRequest();

    $response = $this->post(route('payment-requests.comments.store', $paymentRequest), [
        'body' => 'Hello',
    ]);

    $response->assertRedirect(route('login'));
});
test('authenticated user can post comment', function () {
    $paymentRequest = ownedPaymentRequest();

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
});
test('comment body is required', function () {
    $paymentRequest = ownedPaymentRequest();

    $response = $this->actingAs($this->user)->post(
        route('payment-requests.comments.store', $paymentRequest),
        ['body' => ''],
    );

    $response->assertSessionHasErrors(['body']);
});
test('comment body max length is enforced', function () {
    $paymentRequest = ownedPaymentRequest();

    $response = $this->actingAs($this->user)->post(
        route('payment-requests.comments.store', $paymentRequest),
        ['body' => str_repeat('a', 2001)],
    );

    $response->assertSessionHasErrors(['body']);
});
test('user outside branch cannot post comment', function () {
    $paymentRequest = ownedPaymentRequest();
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
});
test('in branch user not in approval chain cannot post comment', function () {
    $paymentRequest = ownedPaymentRequest();
    $bystander = User::factory()->create(['branch_id' => $this->branch->id, 'operational_branch_id' => $this->branch->id]);
    $bystander->assignRole($this->role);

    $response = $this->actingAs($bystander)->post(
        route('payment-requests.comments.store', $paymentRequest),
        ['body' => 'Not my business.'],
    );

    $response->assertForbidden();
});
test('approver on active stage can post comment', function () {
    $engine = app(WorkflowEngineService::class);
    $paymentRequest = ownedPaymentRequest();

    ['template' => $template, 'role' => $role] = makeApprovalStageForCommentsController();
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
});
test('user can delete own comment', function () {
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
});
test('user cannot delete another users comment', function () {
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
});
