<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Tenant\Branch;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use Tests\TenantAppTestCase;

class PaymentRequestServiceTest extends TenantAppTestCase
{
    private function makeService(): PaymentRequestService
    {
        return app(PaymentRequestService::class);
    }

    // ── cancel() without active workflow instance ─────────────────────────────

    public function test_cancel_without_active_instance_sets_status_to_cancelled(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $this->makeService()->cancel($request, $this->user);

        $this->assertDatabaseHas('payment_requests', [
            'id' => $request->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cancel_logs_activity(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $this->makeService()->cancel($request, $this->user);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => PaymentRequest::class,
            'subject_id' => $request->id,
            'event' => 'request.cancelled',
        ]);
    }

    // ── cancel() with active workflow instance ────────────────────────────────

    public function test_cancel_with_active_instance_cancels_instance_and_stages(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stageDef = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);

        $request = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);

        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $request->id,
            'status' => 'in_progress',
        ]);

        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stageDef->id,
            'status' => 'pending',
            'started_at' => null,
        ]);

        $this->makeService()->cancel($request, $this->user);

        $this->assertDatabaseHas('payment_requests', ['id' => $request->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('workflow_instances', ['id' => $instance->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('workflow_instance_stages', [
            'workflow_instance_id' => $instance->id,
            'status' => 'cancelled',
        ]);
    }

    public function test_cancel_with_active_instance_cancels_both_pending_and_active_stages(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stageDef = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);

        $request = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);

        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $request->id,
            'status' => 'in_progress',
        ]);

        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stageDef->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stageDef->id,
            'status' => 'pending',
        ]);

        $this->makeService()->cancel($request, $this->user);

        $cancelledCount = WorkflowInstanceStage::where('workflow_instance_id', $instance->id)
            ->where('status', 'cancelled')
            ->count();

        $this->assertEquals(2, $cancelledCount);
    }

    // ── submit() — branch-specific template selection ─────────────────────────

    public function test_submit_uses_branch_specific_template_when_available(): void
    {
        $branch = Branch::factory()->create();
        $masterTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);
        $branchTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => $branch->id]);

        WorkflowStage::factory()->create(['workflow_template_id' => $branchTemplate->id, 'display_order' => 1]);

        $request = PaymentRequest::factory()->advance()->create([
            'status' => 'draft',
            'branch_id' => $branch->id,
        ]);

        $this->makeService()->submit($request, $this->user);

        $instance = WorkflowInstance::where('workflowable_id', $request->id)
            ->where('workflowable_type', PaymentRequest::class)
            ->firstOrFail();

        $this->assertEquals($branchTemplate->id, $instance->workflow_template_id);
        $this->assertNotEquals($masterTemplate->id, $instance->workflow_template_id);
    }

    public function test_submit_falls_back_to_master_template_when_no_branch_template(): void
    {
        $branch = Branch::factory()->create();
        $masterTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);

        WorkflowStage::factory()->create(['workflow_template_id' => $masterTemplate->id, 'display_order' => 1]);

        $request = PaymentRequest::factory()->advance()->create([
            'status' => 'draft',
            'branch_id' => $branch->id,
        ]);

        $this->makeService()->submit($request, $this->user);

        $instance = WorkflowInstance::where('workflowable_id', $request->id)
            ->where('workflowable_type', PaymentRequest::class)
            ->firstOrFail();

        $this->assertEquals($masterTemplate->id, $instance->workflow_template_id);
    }
}
