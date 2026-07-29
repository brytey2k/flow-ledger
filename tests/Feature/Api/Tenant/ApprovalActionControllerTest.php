<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;

function submitRequestForApprovalForApprovalActionController(): WorkflowInstanceStage
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
test('approve completes the stage', function () {
    $instanceStage = submitRequestForApprovalForApprovalActionController();

    $this->postJson("/api/approvals/{$instanceStage->id}/approve", [
        'action' => 'approve',
        'comment' => 'Looks good',
    ])->assertOk()
        ->assertJsonPath('data.status', 'approved');
});
test('approve requires permission', function () {
    $instanceStage = submitRequestForApprovalForApprovalActionController();

    $this->role->revokePermissionTo(PermissionKey::ApproveRequests->value);
    $this->user->unsetRelation('roles')->unsetRelation('permissions');

    $this->postJson("/api/approvals/{$instanceStage->id}/approve", ['action' => 'approve'])
        ->assertForbidden();
});
test('approve 403 when user role not on stage', function () {
    $instanceStage = submitRequestForApprovalForApprovalActionController();
    $instanceStage->stage->roles()->detach($this->role->id);

    $this->postJson("/api/approvals/{$instanceStage->id}/approve", ['action' => 'approve'])
        ->assertForbidden();
});
test('reject cancels the workflow', function () {
    $instanceStage = submitRequestForApprovalForApprovalActionController();

    $this->postJson("/api/approvals/{$instanceStage->id}/reject", [
        'action' => 'reject',
        'comment' => 'Not approved',
    ])->assertOk();

    expect($instanceStage->fresh()->status)->toEqual('rejected');
});
test('send back transitions request', function () {
    $instanceStage = submitRequestForApprovalForApprovalActionController();
    $subjectId = $instanceStage->instance->subject_id ?? $instanceStage->instance->workflowable_id;

    $this->postJson("/api/approvals/{$instanceStage->id}/send-back", [
        'action' => 'send_back',
        'comment' => 'Please fix this',
    ])->assertOk();

    $pr = PaymentRequest::find($subjectId);
    expect($pr->status)->toEqual('sent_back');
});
test('send back 422 when stage not active', function () {
    $instanceStage = submitRequestForApprovalForApprovalActionController();
    app(App\Services\WorkflowEngineService::class)->approve($instanceStage, $this->user, null);

    $this->postJson("/api/approvals/{$instanceStage->id}/send-back", [
        'action' => 'send_back',
        'comment' => 'too late',
    ])->assertStatus(422);
});
