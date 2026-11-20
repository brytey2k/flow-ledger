<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Support\Str;

function ownedDraftRequest(array $overrides = []): PaymentRequest
{
    $staff = Staff::factory()->withUser(test()->user)->withBranch(test()->branch)->create();

    return PaymentRequest::factory()->advance()->create(array_merge([
        'status' => 'draft',
        'branch_id' => test()->branch->id,
        'staff_id' => $staff->id,
    ], $overrides));
}
test('guest is redirected from submit', function () {
    $request = PaymentRequest::factory()->create();

    $response = $this->post(route('payment-requests.submit', $request));

    $response->assertRedirect(route('login'));
});
test('user without permission cannot submit', function () {
    $request = PaymentRequest::factory()->create();
    $this->role->revokePermissionTo('create payment request');

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertForbidden();
});
test('user cannot submit request from different branch', function () {
    $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertForbidden();
});
test('user cannot submit request they do not own', function () {
    $otherStaff = Staff::factory()->withBranch($this->branch)->create();
    $request = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'branch_id' => $this->branch->id,
        'staff_id' => $otherStaff->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertRedirect(route('payment-requests.show', $request));
    $response->assertSessionHas('error');
    expect($request->fresh()->status)->toBe('draft');
});
test('submit starts workflow and redirects', function () {
    $request = ownedDraftRequest();
    $template = WorkflowTemplate::factory()->advance()->create();
    WorkflowStage::factory()->for($template, 'template')->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertRedirect(route('payment-requests.show', $request));
    $response->assertSessionHas('success');

    $request->refresh();
    expect($request->status)->toBe('in_workflow');
    expect($request->submitted_at)->not->toBeNull();
    expect($request->activeWorkflowInstance)->toBeInstanceOf(WorkflowInstance::class);
});
test('cannot submit non draft request', function () {
    $request = ownedDraftRequest(['status' => 'in_workflow']);

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertRedirect(route('payment-requests.show', $request));
    $response->assertSessionHas('error');
});
test('cannot submit when no workflow template exists', function () {
    WorkflowTemplate::query()->delete();
    $request = ownedDraftRequest();

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertRedirect(route('payment-requests.show', $request));
    $response->assertSessionHas('error');

    $request->refresh();
    expect($request->status)->toBe('draft');
});
test('cannot submit when workflow template has no stages', function () {
    $request = ownedDraftRequest();
    WorkflowTemplate::factory()->advance()->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertRedirect(route('payment-requests.show', $request));
    $response->assertSessionHas('error');

    $request->refresh();
    expect($request->status)->toBe('draft');
});
test('submit uses the current published template, not an older version', function () {
    WorkflowTemplate::query()->delete();
    $request = ownedDraftRequest();
    $groupId = (string) Str::uuid();
    // An older, superseded version that has no stages sorts first.
    WorkflowTemplate::factory()->advance()->create(['template_group_id' => $groupId, 'version' => 1, 'is_current' => false]);
    $current = WorkflowTemplate::factory()->advance()->create(['template_group_id' => $groupId, 'version' => 2, 'is_current' => true]);
    WorkflowStage::factory()->for($current, 'template')->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertSessionHas('success');
    expect($request->fresh()->activeWorkflowInstance->workflow_template_id)->toBe($current->id);
});
test('submit ignores an unpublished draft template', function () {
    WorkflowTemplate::query()->delete();
    $request = ownedDraftRequest();
    $draft = WorkflowTemplate::factory()->advance()->draft()->create();
    WorkflowStage::factory()->for($draft, 'template')->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertRedirect(route('payment-requests.show', $request));
    $response->assertSessionHas('error', __('flash.requests.missing_workflow_template'));
    expect($request->fresh()->status)->toBe('draft');
});
test('submit prefers the branch template over the master template', function () {
    WorkflowTemplate::query()->delete();
    $request = ownedDraftRequest();
    // The master template has no stages; checking it instead of the branch template would block the submit.
    WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);
    $branchTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => $this->branch->id]);
    WorkflowStage::factory()->for($branchTemplate, 'template')->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertSessionHas('success');
    expect($request->fresh()->activeWorkflowInstance->workflow_template_id)->toBe($branchTemplate->id);
});
test('submit ignores another branch template and falls back to the master template', function () {
    WorkflowTemplate::query()->delete();
    $request = ownedDraftRequest();
    $otherBranch = Branch::factory()->create(['level_id' => $this->level->id]);
    $otherBranchTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => $otherBranch->id]);
    WorkflowStage::factory()->for($otherBranchTemplate, 'template')->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertSessionHas('error', __('flash.requests.missing_workflow_template'));
    expect($request->fresh()->status)->toBe('draft');
});
test('expense submit reports a missing workflow template instead of failing', function () {
    WorkflowTemplate::query()->delete();
    $request = ownedDraftRequest(['type' => 'expense']);

    $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

    $response->assertRedirect(route('payment-requests.show', $request));
    $response->assertSessionHas('error', __('flash.requests.missing_workflow_template'));
    expect($request->fresh()->status)->toBe('draft');
});
