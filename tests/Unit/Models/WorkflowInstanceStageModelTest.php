<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Tests\TenantAppTestCase;

class WorkflowInstanceStageModelTest extends TenantAppTestCase
{
    private function makeStage(string $status = 'active'): WorkflowInstanceStage
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        $stageDef = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $pr = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);
        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $pr->id,
            'status' => 'in_progress',
        ]);

        return WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stageDef->id,
            'status' => $status,
            'started_at' => now(),
        ]);
    }

    public function test_is_active_returns_true_when_status_is_active(): void
    {
        $stage = $this->makeStage('active');

        $this->assertTrue($stage->isActive());
    }

    public function test_is_active_returns_false_when_status_is_not_active(): void
    {
        $stage = $this->makeStage('approved');

        $this->assertFalse($stage->isActive());
    }

    public function test_is_terminal_returns_true_for_approved_status(): void
    {
        $stage = $this->makeStage('approved');

        $this->assertTrue($stage->isTerminal());
    }

    public function test_is_terminal_returns_false_for_active_status(): void
    {
        $stage = $this->makeStage('active');

        $this->assertFalse($stage->isTerminal());
    }

    public function test_instance_relation_loads_workflow_instance(): void
    {
        $stage = $this->makeStage();

        $this->assertInstanceOf(WorkflowInstance::class, $stage->instance);
    }

    public function test_stage_relation_loads_workflow_stage_definition(): void
    {
        $stage = $this->makeStage();

        $this->assertInstanceOf(WorkflowStage::class, $stage->stage);
    }
}
