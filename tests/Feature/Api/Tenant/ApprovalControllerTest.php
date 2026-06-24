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

class ApprovalControllerTest extends ApiTenantTestCase
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

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_active_stages_for_user_role(): void
    {
        $this->submitRequestForApproval();

        $this->getJson('/api/approvals')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonPath('meta.total', 1);
    }

    public function test_index_excludes_stages_not_assigned_to_user_role(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        // Do NOT attach user's role — attach a different one
        $otherRole = \App\Models\Role::create(['name' => 'other_approver_' . uniqid(), 'guard_name' => 'web']);
        $stage->roles()->attach($otherRole->id);

        $pr = PaymentRequest::factory()->advance()->create([
            'branch_id' => $this->branch->id,
            'status' => 'draft',
        ]);
        app(PaymentRequestService::class)->submit($pr);

        $this->getJson('/api/approvals')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    public function test_index_requires_approve_requests_permission(): void
    {
        $this->role->revokePermissionTo(PermissionKey::ApproveRequests->value);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');

        $this->getJson('/api/approvals')->assertForbidden();
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_show_returns_stage_detail(): void
    {
        $instanceStage = $this->submitRequestForApproval();

        $this->getJson("/api/approvals/{$instanceStage->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $instanceStage->id);
    }

    public function test_show_requires_approve_requests_permission(): void
    {
        $instanceStage = $this->submitRequestForApproval();

        $this->role->revokePermissionTo(PermissionKey::ApproveRequests->value);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');

        $this->getJson("/api/approvals/{$instanceStage->id}")->assertForbidden();
    }
}
