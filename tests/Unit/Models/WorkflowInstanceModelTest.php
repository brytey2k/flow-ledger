<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Tests\TenantAppTestCase;

class WorkflowInstanceModelTest extends TenantAppTestCase
{
    private function makeInstance(): WorkflowInstance
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        $pr = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);

        return WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $pr->id,
            'status' => 'in_progress',
        ]);
    }

    public function test_sent_back_stage_returns_null_when_no_sent_back_stage(): void
    {
        $instance = $this->makeInstance();

        $this->assertNull($instance->sentBackStage());
    }

    public function test_sent_back_stage_returns_the_stage_when_set(): void
    {
        $instance = $this->makeInstance();
        $template = WorkflowTemplate::factory()->create(['type' => 'expense']);
        $stageDef = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

        $stage = WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stageDef->id,
            'status' => 'sent_back',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $instance->update(['sent_back_to_stage_id' => $stage->id]);
        $instance->refresh();

        $sentBack = $instance->sentBackStage();
        $this->assertNotNull($sentBack);
        $this->assertEquals($stage->id, $sentBack->id);
    }

    public function test_is_in_progress_returns_true_when_status_is_in_progress(): void
    {
        $instance = $this->makeInstance();

        $this->assertTrue($instance->isInProgress());
    }

    public function test_is_in_progress_returns_false_when_status_is_not_in_progress(): void
    {
        $instance = $this->makeInstance();
        $instance->update(['status' => 'completed']);

        $this->assertFalse($instance->isInProgress());
    }

    public function test_active_instance_stages_relation_filters_by_active_status(): void
    {
        $instance = $this->makeInstance();
        $template = WorkflowTemplate::factory()->create(['type' => 'expense']);
        $stageDef = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

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

        $this->assertCount(1, $instance->activeInstanceStages);
    }
}
