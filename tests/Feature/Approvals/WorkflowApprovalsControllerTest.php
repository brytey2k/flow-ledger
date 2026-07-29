<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\Department;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;

function submitRequestWithTemplate(): array
{
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);
    $stage->roles()->attach(test()->role->id);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    app(PaymentRequestService::class)->submit($paymentRequest);

    $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();

    return [$paymentRequest, $instanceStage];
}
test('guest is redirected from index', function () {
    $response = $this->get(route('approvals.index'));

    $response->assertRedirect(route('login'));
});
test('guest is redirected from show', function () {
    [, $instanceStage] = submitRequestWithTemplate();

    $response = $this->get(route('approvals.show', $instanceStage));

    $response->assertRedirect(route('login'));
});
test('user without permission cannot access index', function () {
    $this->role->revokePermissionTo('approve requests');

    $response = $this->actingAs($this->user)->get(route('approvals.index'));

    $response->assertForbidden();
});
test('user whose role is not on stage cannot view review screen', function () {
    [, $instanceStage] = submitRequestWithTemplate();

    // Detach this role from the stage so canUserActOnStage returns false
    $instanceStage->stage->roles()->detach($this->role->id);

    $response = $this->actingAs($this->user)->get(route('approvals.show', $instanceStage));

    $response->assertForbidden();
});
test('index shows active stages for user role', function () {
    submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->get(route('approvals.index'));

    $response->assertOk();
    $response->assertViewIs('tenant.approvals.index');
    $response->assertViewHas('instanceStages');
});
test('index shows empty state when nothing pending', function () {
    $response = $this->actingAs($this->user)->get(route('approvals.index'));

    $response->assertOk();
    $response->assertSee('All caught up');
});
test('index excludes branch scoped stage when user branch does not match', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
        'scope_to_branch' => true,
    ]);
    $stage->roles()->attach($this->role->id);

    $otherBranch = Branch::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'branch_id' => $otherBranch->id,
    ]);
    app(PaymentRequestService::class)->submit($paymentRequest);

    // User's staff branch differs from the request's branch
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

    $response = $this->actingAs($this->user)->get(route('approvals.index'));

    $response->assertOk();
    expect($response->viewData('instanceStages'))->toHaveCount(0);
});
test('index includes branch scoped stage when user branch matches', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
        'scope_to_branch' => true,
    ]);
    $stage->roles()->attach($this->role->id);

    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'branch_id' => $this->branch->id,
    ]);
    app(PaymentRequestService::class)->submit($paymentRequest);

    // User's staff branch matches the request's branch
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

    $response = $this->actingAs($this->user)->get(route('approvals.index'));

    $response->assertOk();
    expect($response->viewData('instanceStages'))->toHaveCount(1);
});
test('index excludes department scoped stage when user department does not match', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
        'scope_to_department' => true,
    ]);
    $stage->roles()->attach($this->role->id);

    /** @var User $submitter */
    $submitter = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $submitterDept = Department::factory()->create();
    Staff::factory()->withUser($submitter)->create(['department_id' => $submitterDept->id]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
    app(PaymentRequestService::class)->submit($paymentRequest, $submitter);

    // User's department differs from the submitter's department
    $otherDept = Department::factory()->create();
    Staff::factory()->withUser($this->user)->create(['department_id' => $otherDept->id]);

    $response = $this->actingAs($this->user)->get(route('approvals.index'));

    $response->assertOk();
    expect($response->viewData('instanceStages'))->toHaveCount(0);
});
test('index includes department scoped stage when user department matches', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
        'scope_to_department' => true,
    ]);
    $stage->roles()->attach($this->role->id);

    /** @var User $submitter */
    $submitter = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $dept = Department::factory()->create();
    Staff::factory()->withUser($submitter)->create(['department_id' => $dept->id]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
    app(PaymentRequestService::class)->submit($paymentRequest, $submitter);

    // User's department matches the submitter's department
    Staff::factory()->withUser($this->user)->create(['department_id' => $dept->id]);

    $response = $this->actingAs($this->user)->get(route('approvals.index'));

    $response->assertOk();
    expect($response->viewData('instanceStages'))->toHaveCount(1);
});
test('index includes branch scoped stage for user without staff profile', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
        'scope_to_branch' => true,
    ]);
    $stage->roles()->attach($this->role->id);

    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'branch_id' => $this->branch->id,
    ]);
    app(PaymentRequestService::class)->submit($paymentRequest);

    // User has no staff profile — should bypass branch scope and see all scoped stages
    $response = $this->actingAs($this->user)->get(route('approvals.index'));

    $response->assertOk();
    expect($response->viewData('instanceStages'))->toHaveCount(1);
});
test('index includes department scoped stage for user without staff profile', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
        'scope_to_department' => true,
    ]);
    $stage->roles()->attach($this->role->id);

    /** @var User $submitter */
    $submitter = User::factory()->create([
        'branch_id' => $this->branch->id,
        'operational_branch_id' => $this->branch->id,
    ]);
    $dept = Department::factory()->create();
    Staff::factory()->withUser($submitter)->create(['department_id' => $dept->id]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
    app(PaymentRequestService::class)->submit($paymentRequest, $submitter);

    // User has no staff profile — should bypass department scope and see all scoped stages
    $response = $this->actingAs($this->user)->get(route('approvals.index'));

    $response->assertOk();
    expect($response->viewData('instanceStages'))->toHaveCount(1);
});
test('review screen renders for eligible approver', function () {
    [, $instanceStage] = submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->get(route('approvals.show', $instanceStage));

    $response->assertOk();
    $response->assertViewIs('tenant.approvals.show');
    $response->assertViewHas('instanceStage');
});
test('review screen shows workflow version badge', function () {
    [, $instanceStage] = submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->get(route('approvals.show', $instanceStage));

    $response->assertOk();
    $response->assertSee("Version {$instanceStage->instance->template->version}");
});
test('review screen shows action form when stage is active', function () {
    [, $instanceStage] = submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->get(route('approvals.show', $instanceStage));

    $response->assertOk();
    $response->assertSee('id="approval-form"', false);
});
test('review screen hides action form when stage already resolved', function () {
    [, $instanceStage] = submitRequestWithTemplate();
    $instanceStage->update(['status' => 'approved']);

    $response = $this->actingAs($this->user)->get(route('approvals.show', $instanceStage));

    $response->assertOk();
    $response->assertDontSee('id="approval-form"', false);
    $response->assertSee(__('approvals.show.already_resolved_heading'));
});
test('approve marks stage approved and redirects', function () {
    [$paymentRequest, $instanceStage] = submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
        'action' => 'approve',
        'comment' => 'Looks good.',
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('success');

    $instanceStage->refresh();
    expect($instanceStage->status)->toBe('approved');
});
test('approve completes workflow when last stage', function () {
    [$paymentRequest, $instanceStage] = submitRequestWithTemplate();

    $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
        'action' => 'approve',
    ]);

    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBe('approved');
    expect($paymentRequest->approved_at)->not->toBeNull();
});
test('reject cancels workflow and redirects', function () {
    [$paymentRequest, $instanceStage] = submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
        'action' => 'reject',
        'comment' => 'Not justified.',
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));

    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBe('cancelled');
});
test('reject requires comment', function () {
    [, $instanceStage] = submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
        'action' => 'reject',
    ]);

    $response->assertSessionHasErrors(['comment']);
});
test('send back sets request to sent back status', function () {
    [$paymentRequest, $instanceStage] = submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
        'action' => 'send_back',
        'comment' => 'Please revise the amounts.',
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));

    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBe('sent_back');

    $instanceStage->refresh();
    expect($instanceStage->status)->toBe('sent_back');
});
test('send back requires comment', function () {
    [, $instanceStage] = submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
        'action' => 'send_back',
    ]);

    $response->assertSessionHasErrors(['comment']);
});
test('start workflow persists branch and department on instance', function () {
    $dept = Department::factory()->create();
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create(['department_id' => $dept->id]);

    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'branch_id' => $this->branch->id,
    ]);

    $template = WorkflowTemplate::factory()->advance()->create();
    WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);

    app(PaymentRequestService::class)->submit($paymentRequest, $this->user);

    $instance = $paymentRequest->workflowInstances()->first();
    expect($instance->branch_id)->toBe($this->branch->id);
    expect($instance->department_id)->toBe($dept->id);
});
test('store requires valid action', function () {
    [, $instanceStage] = submitRequestWithTemplate();

    $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
        'action' => 'invalid',
    ]);

    $response->assertSessionHasErrors(['action']);
});
test('user whose role is not on stage cannot act', function () {
    [, $instanceStage] = submitRequestWithTemplate();
    $instanceStage->stage->roles()->detach($this->role->id);

    $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
        'action' => 'approve',
    ]);

    $response->assertForbidden();
});
