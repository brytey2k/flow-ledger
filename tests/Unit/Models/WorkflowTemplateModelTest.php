<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\Branch;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TenantAppTestCase;

class WorkflowTemplateModelTest extends TenantAppTestCase
{
    // ── resolveForBranch() ────────────────────────────────────────────────────

    public function test_resolve_for_branch_returns_branch_specific_template_when_exists(): void
    {
        $branch = Branch::factory()->create();
        $master = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);
        $branchTemplate = WorkflowTemplate::factory()->advance()->create(['branch_id' => $branch->id]);

        $resolved = WorkflowTemplate::resolveForBranch('advance', $branch->id);

        $this->assertTrue($resolved->is($branchTemplate));
        $this->assertFalse($resolved->is($master));
    }

    public function test_resolve_for_branch_falls_back_to_master_when_no_branch_template_exists(): void
    {
        $branch = Branch::factory()->create();
        $master = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);

        $resolved = WorkflowTemplate::resolveForBranch('advance', $branch->id);

        $this->assertTrue($resolved->is($master));
    }

    public function test_resolve_for_branch_with_null_branch_id_returns_master_template(): void
    {
        $master = WorkflowTemplate::factory()->retirement()->create(['branch_id' => null]);

        $resolved = WorkflowTemplate::resolveForBranch('retirement', null);

        $this->assertTrue($resolved->is($master));
    }

    public function test_resolve_for_branch_throws_when_no_master_template_exists(): void
    {
        $this->expectException(ModelNotFoundException::class);

        WorkflowTemplate::resolveForBranch('expense', null);
    }

    public function test_resolve_for_branch_throws_when_branch_has_no_template_and_no_master_exists(): void
    {
        $branch = Branch::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        WorkflowTemplate::resolveForBranch('advance', $branch->id);
    }

    // ── branch() relationship ─────────────────────────────────────────────────

    public function test_branch_relationship_returns_null_for_master_template(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create(['branch_id' => null]);

        $this->assertNull($template->branch);
    }

    public function test_branch_relationship_returns_branch_for_branch_specific_template(): void
    {
        $branch = Branch::factory()->create();
        $template = WorkflowTemplate::factory()->advance()->create(['branch_id' => $branch->id]);

        $this->assertNotNull($template->branch);
        $this->assertTrue($template->branch->is($branch));
    }
}
