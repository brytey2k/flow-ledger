<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;

function submitRequestForApprovalForApprovalController(): WorkflowInstanceStage
{
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);
    $stage->roles()->attach(test()->role->id);

    $pr = PaymentRequest::factory()->advance()->create([
        'branch_id' => test()->branch->id,
        'status' => 'draft',
    ]);

    app(PaymentRequestService::class)->submit($pr);

    return WorkflowInstanceStage::where('status', 'active')->latest()->firstOrFail();
}
test('index returns active stages for user role', function () {
    submitRequestForApprovalForApprovalController();

    $this->getJson('/api/approvals')
        ->assertOk()
        ->assertJsonStructure(['data', 'meta'])
        ->assertJsonPath('meta.total', 1);
});
test('index excludes stages not assigned to user role', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);

    // Do NOT attach user's role — attach a different one
    $otherRole = App\Models\Role::create(['name' => 'other_approver_' . uniqid(), 'guard_name' => 'web']);
    $stage->roles()->attach($otherRole->id);

    $pr = PaymentRequest::factory()->advance()->create([
        'branch_id' => $this->branch->id,
        'status' => 'draft',
    ]);
    app(PaymentRequestService::class)->submit($pr);

    $this->getJson('/api/approvals')
        ->assertOk()
        ->assertJsonPath('meta.total', 0);
});
test('index requires approve requests permission', function () {
    $this->role->revokePermissionTo(PermissionKey::ApproveRequests->value);
    $this->user->unsetRelation('roles')->unsetRelation('permissions');

    $this->getJson('/api/approvals')->assertForbidden();
});
test('show returns stage detail', function () {
    $instanceStage = submitRequestForApprovalForApprovalController();

    $this->getJson("/api/approvals/{$instanceStage->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $instanceStage->id);
});
test('show requires approve requests permission', function () {
    $instanceStage = submitRequestForApprovalForApprovalController();

    $this->role->revokePermissionTo(PermissionKey::ApproveRequests->value);
    $this->user->unsetRelation('roles')->unsetRelation('permissions');

    $this->getJson("/api/approvals/{$instanceStage->id}")->assertForbidden();
});
