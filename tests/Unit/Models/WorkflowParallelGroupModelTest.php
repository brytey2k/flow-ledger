<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Tests\TenantAppTestCase;

class WorkflowParallelGroupModelTest extends TenantAppTestCase
{
    public function test_template_relation_loads_workflow_template(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);

        $this->assertEquals($template->id, $group->template->id);
    }

    public function test_stages_relation_returns_associated_stages(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance']);
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
        ]);

        $this->assertCount(1, $group->stages);
    }

    public function test_require_all_casts_to_boolean(): void
    {
        $group = WorkflowParallelGroup::factory()->create(['require_all' => true]);

        $this->assertTrue($group->require_all);
    }
}
