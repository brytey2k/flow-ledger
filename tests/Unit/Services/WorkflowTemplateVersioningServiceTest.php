<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\Tenant\WorkflowTemplateStatus;
use App\Models\Role;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowParallelGroup;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowTemplateVersioningService;
use InvalidArgumentException;
use Tests\TenantAppTestCase;

class WorkflowTemplateVersioningServiceTest extends TenantAppTestCase
{
    private function makeService(): WorkflowTemplateVersioningService
    {
        return app(WorkflowTemplateVersioningService::class);
    }

    // ── shouldFork() ─────────────────────────────────────────────────────────

    public function test_should_fork_returns_true_when_structural_and_has_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $this->markTemplateActive($template);

        $this->assertTrue($this->makeService()->shouldFork($template, isStructuralChange: true));
    }

    public function test_should_fork_returns_false_when_structural_but_no_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();

        $this->assertFalse($this->makeService()->shouldFork($template, isStructuralChange: true));
    }

    public function test_should_fork_returns_false_when_not_structural_regardless_of_active_instances(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $this->markTemplateActive($template);

        $this->assertFalse($this->makeService()->shouldFork($template, isStructuralChange: false));
    }

    // ── draftFor() ───────────────────────────────────────────────────────────

    public function test_draft_for_returns_null_when_no_draft_exists(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();

        $this->assertNull($this->makeService()->draftFor($template));
    }

    public function test_draft_for_returns_itself_when_called_on_a_draft(): void
    {
        $draft = WorkflowTemplate::factory()->advance()->draft()->create();

        $this->assertSame($draft->id, $this->makeService()->draftFor($draft)->id);
    }

    public function test_draft_for_finds_existing_draft_from_the_published_sibling(): void
    {
        $current = WorkflowTemplate::factory()->advance()->create();
        $draft = WorkflowTemplate::factory()->advance()->draft()->create([
            'template_group_id' => $current->template_group_id,
            'version' => 2,
        ]);

        $found = $this->makeService()->draftFor($current);

        $this->assertNotNull($found);
        $this->assertSame($draft->id, $found->id);
    }

    // ── forkDraft() ──────────────────────────────────────────────────────────

    public function test_fork_draft_creates_new_row_with_incremented_version_and_same_group_id(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();

        $fork = $this->makeService()->forkDraft($template);

        $this->assertNotSame($template->id, $fork->newTemplate->id);
        $this->assertSame(2, $fork->newTemplate->version);
        $this->assertSame($template->template_group_id, $fork->newTemplate->template_group_id);
        $this->assertFalse($fork->newTemplate->is_current);
        $this->assertSame(WorkflowTemplateStatus::Draft->value, $fork->newTemplate->status);
    }

    public function test_fork_draft_leaves_old_row_is_current_untouched(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();

        $this->makeService()->forkDraft($template);

        $this->assertTrue($template->fresh()->is_current);
    }

    public function test_fork_draft_preserves_type_and_branch_id_from_source(): void
    {
        $branch = \App\Models\Tenant\Branch::factory()->create();
        $template = WorkflowTemplate::factory()->retirement()->create(['branch_id' => $branch->id]);

        $fork = $this->makeService()->forkDraft($template);

        $this->assertSame('retirement', $fork->newTemplate->type);
        $this->assertSame($branch->id, $fork->newTemplate->branch_id);
    }

    public function test_fork_draft_clones_all_stages_with_correct_attributes(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $role = Role::create(['name' => 'fork_role', 'guard_name' => 'web']);
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'name' => 'Finance Review',
            'display_order' => 2,
            'skip_below_amount' => 250.00,
        ]);
        $stage->roles()->sync([$role->id]);

        $fork = $this->makeService()->forkDraft($template);

        $clonedStage = $fork->newTemplate->stages()->firstOrFail();
        $this->assertSame('Finance Review', $clonedStage->name);
        $this->assertSame(2, $clonedStage->display_order);
        $this->assertEqualsWithDelta(250.00, (float) $clonedStage->skip_below_amount, 0.01);
        $this->assertNotSame($stage->id, $clonedStage->id);
    }

    public function test_fork_draft_clones_all_parallel_groups(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        WorkflowParallelGroup::factory()->create([
            'workflow_template_id' => $template->id,
            'name' => 'Finance & HR',
            'require_all' => true,
        ]);

        $fork = $this->makeService()->forkDraft($template);

        $clonedGroup = $fork->newTemplate->parallelGroups()->firstOrFail();
        $this->assertSame('Finance & HR', $clonedGroup->name);
        $this->assertTrue($clonedGroup->require_all);
    }

    public function test_fork_draft_remaps_parallel_group_id_on_cloned_stages_correctly(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $group = WorkflowParallelGroup::factory()->create(['workflow_template_id' => $template->id]);
        WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'parallel_group_id' => $group->id,
        ]);

        $fork = $this->makeService()->forkDraft($template);

        $clonedStage = $fork->newTemplate->stages()->firstOrFail();
        $clonedGroup = $fork->newTemplate->parallelGroups()->firstOrFail();
        $this->assertSame($clonedGroup->id, $clonedStage->parallel_group_id);
        $this->assertNotSame($group->id, $clonedStage->parallel_group_id);
    }

    public function test_fork_draft_clones_stage_role_pivots(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $role = Role::create(['name' => 'fork_role_pivot', 'guard_name' => 'web']);
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $stage->roles()->sync([$role->id]);

        $fork = $this->makeService()->forkDraft($template);

        $clonedStage = $fork->newTemplate->stages()->firstOrFail();
        $this->assertTrue($clonedStage->roles->pluck('id')->contains($role->id));
    }

    public function test_fork_draft_returns_stage_id_map_covering_every_original_stage(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stageOne = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $stageTwo = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

        $fork = $this->makeService()->forkDraft($template);

        $this->assertArrayHasKey($stageOne->id, $fork->stageIdMap);
        $this->assertArrayHasKey($stageTwo->id, $fork->stageIdMap);
        $this->assertCount(2, $fork->stageIdMap);
    }

    // ── publish() ────────────────────────────────────────────────────────────

    public function test_publish_marks_draft_current_and_published(): void
    {
        $current = WorkflowTemplate::factory()->advance()->create();
        $draft = WorkflowTemplate::factory()->advance()->draft()->create([
            'template_group_id' => $current->template_group_id,
            'version' => 2,
        ]);

        $this->makeService()->publish($draft);

        $draft->refresh();
        $this->assertTrue($draft->is_current);
        $this->assertSame(WorkflowTemplateStatus::Published->value, $draft->status);
    }

    public function test_publish_flips_previous_current_sibling_to_not_current(): void
    {
        $current = WorkflowTemplate::factory()->advance()->create();
        $draft = WorkflowTemplate::factory()->advance()->draft()->create([
            'template_group_id' => $current->template_group_id,
            'version' => 2,
        ]);

        $this->makeService()->publish($draft);

        $this->assertFalse($current->fresh()->is_current);
    }

    public function test_publish_throws_when_template_is_not_a_draft(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->makeService()->publish($template);
    }

    // ── discard() ────────────────────────────────────────────────────────────

    public function test_discard_deletes_the_draft_row(): void
    {
        $draft = WorkflowTemplate::factory()->advance()->draft()->create();

        $this->makeService()->discard($draft);

        $this->assertDatabaseMissing('workflow_templates', ['id' => $draft->id]);
    }

    public function test_discard_cascades_cloned_stages_and_groups(): void
    {
        $current = WorkflowTemplate::factory()->advance()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $current->id]);
        $fork = $this->makeService()->forkDraft($current);

        $this->makeService()->discard($fork->newTemplate);

        $this->assertDatabaseMissing('workflow_stages', ['workflow_template_id' => $fork->newTemplate->id]);
    }

    public function test_discard_throws_when_template_is_not_a_draft(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();

        $this->expectException(InvalidArgumentException::class);

        $this->makeService()->discard($template);
    }

    private function markTemplateActive(WorkflowTemplate $template): void
    {
        $subject = PaymentRequest::factory()->inWorkflow()->create();
        WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $subject->id,
            'status' => 'in_progress',
        ]);
    }
}
