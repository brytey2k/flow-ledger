<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use Tests\ApiTenantTestCase;

class ApprovalActionControllerTest extends ApiTenantTestCase
{
    private function submitRequestForApproval(): WorkflowInstanceStage
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        $stage->roles()->attach($this->role->id);

        $pr = PaymentRequest::factory()->advance()->create([
            'branch_id' => $this->branch->id,
            'status' => 'draft',
        ]);

        app(PaymentRequestService::class)->submit($pr);

        return WorkflowInstanceStage::where('status', 'active')->latest()->firstOrFail();
    }

    // ── Approve ───────────────────────────────────────────────────────────────

    public function test_approve_completes_the_stage(): void
    {
        $instanceStage = $this->submitRequestForApproval();

        $this->postJson("/api/approvals/{$instanceStage->id}/approve", [
            'action' => 'approve',
            'comment' => 'Looks good',
        ])->assertOk()
            ->assertJsonPath('data.status', 'approved');
    }

    public function test_approve_requires_permission(): void
    {
        $instanceStage = $this->submitRequestForApproval();

        $this->role->revokePermissionTo(PermissionKey::ApproveRequests->value);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');

        $this->postJson("/api/approvals/{$instanceStage->id}/approve", ['action' => 'approve'])
            ->assertForbidden();
    }

    public function test_approve_403_when_user_role_not_on_stage(): void
    {
        $instanceStage = $this->submitRequestForApproval();
        $instanceStage->stage->roles()->detach($this->role->id);

        $this->postJson("/api/approvals/{$instanceStage->id}/approve", ['action' => 'approve'])
            ->assertForbidden();
    }

    // ── Reject ────────────────────────────────────────────────────────────────

    public function test_reject_cancels_the_workflow(): void
    {
        $instanceStage = $this->submitRequestForApproval();

        $this->postJson("/api/approvals/{$instanceStage->id}/reject", [
            'action' => 'reject',
            'comment' => 'Not approved',
        ])->assertOk();

        $this->assertEquals('rejected', $instanceStage->fresh()->status);
    }

    // ── Send Back ─────────────────────────────────────────────────────────────

    public function test_send_back_transitions_request(): void
    {
        $instanceStage = $this->submitRequestForApproval();
        $subjectId = $instanceStage->instance->subject_id ?? $instanceStage->instance->workflowable_id;

        $this->postJson("/api/approvals/{$instanceStage->id}/send-back", [
            'action' => 'send_back',
            'comment' => 'Please fix this',
        ])->assertOk();

        $pr = PaymentRequest::find($subjectId);
        $this->assertEquals('sent_back', $pr->status);
    }

    public function test_send_back_422_when_stage_not_active(): void
    {
        $instanceStage = $this->submitRequestForApproval();
        app(\App\Services\WorkflowEngineService::class)->approve($instanceStage, $this->user, null);

        $this->postJson("/api/approvals/{$instanceStage->id}/send-back", [
            'action' => 'send_back',
            'comment' => 'too late',
        ])->assertStatus(422);
    }
}
