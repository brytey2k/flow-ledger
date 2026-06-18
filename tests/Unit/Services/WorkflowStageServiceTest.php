<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\WorkflowStageDto;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowStageService;
use Tests\TenantAppTestCase;

class WorkflowStageServiceTest extends TenantAppTestCase
{
    private function makeService(): WorkflowStageService
    {
        return app(WorkflowStageService::class);
    }

    private function makeTemplate(): WorkflowTemplate
    {
        return WorkflowTemplate::factory()->advance()->create();
    }

    // ── create() ─────────────────────────────────────────────────────────────

    public function test_create_returns_workflow_stage_instance(): void
    {
        $template = $this->makeTemplate();
        $dto = new WorkflowStageDto(
            name: 'Finance Review',
            displayOrder: 1,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
        );

        $stage = $this->makeService()->create($template, $dto);

        $this->assertInstanceOf(WorkflowStage::class, $stage);
    }

    public function test_create_persists_stage_with_correct_name(): void
    {
        $template = $this->makeTemplate();
        $dto = new WorkflowStageDto(
            name: 'Manager Approval',
            displayOrder: 1,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
        );

        $stage = $this->makeService()->create($template, $dto);

        $this->assertSame('Manager Approval', WorkflowStage::findOrFail($stage->id)->name);
    }

    public function test_create_persists_stage_with_correct_display_order(): void
    {
        $template = $this->makeTemplate();
        $dto = new WorkflowStageDto(
            name: 'Review Stage',
            displayOrder: 3,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
        );

        $stage = $this->makeService()->create($template, $dto);

        $this->assertSame(3, $stage->display_order);
    }

    public function test_create_associates_stage_with_template(): void
    {
        $template = $this->makeTemplate();
        $dto = new WorkflowStageDto(
            name: 'Approval',
            displayOrder: 1,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
        );

        $stage = $this->makeService()->create($template, $dto);

        $this->assertSame($template->id, $stage->workflow_template_id);
    }

    public function test_create_syncs_roles_to_stage(): void
    {
        $template = $this->makeTemplate();
        $dto = new WorkflowStageDto(
            name: 'Review',
            displayOrder: 1,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [$this->role->id],
        );

        $stage = $this->makeService()->create($template, $dto);

        $this->assertTrue($stage->roles->pluck('id')->contains($this->role->id));
    }

    public function test_create_syncs_multiple_roles_to_stage(): void
    {
        $template = $this->makeTemplate();
        $extraRole = \App\Models\Role::create(['name' => 'extra_role_' . \Illuminate\Support\Str::uuid(), 'guard_name' => 'web']);
        $dto = new WorkflowStageDto(
            name: 'Review',
            displayOrder: 1,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [$this->role->id, $extraRole->id],
        );

        $stage = $this->makeService()->create($template, $dto);

        $roleIds = $stage->roles->pluck('id');
        $this->assertTrue($roleIds->contains($this->role->id));
        $this->assertTrue($roleIds->contains($extraRole->id));
    }

    public function test_create_sets_skip_below_amount(): void
    {
        $template = $this->makeTemplate();
        $dto = new WorkflowStageDto(
            name: 'Threshold Stage',
            displayOrder: 1,
            skipBelowAmount: 500.00,
            parallelGroupId: null,
            roleIds: [],
        );

        $stage = $this->makeService()->create($template, $dto);

        $this->assertEqualsWithDelta(500.00, (float) $stage->skip_below_amount, 0.01);
    }

    public function test_create_sets_scope_to_department(): void
    {
        $template = $this->makeTemplate();
        $dto = new WorkflowStageDto(
            name: 'Dept Scoped',
            displayOrder: 1,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
            scopeToDepartment: true,
        );

        $stage = $this->makeService()->create($template, $dto);

        $this->assertTrue($stage->scope_to_department);
    }

    public function test_create_sets_scope_to_branch(): void
    {
        $template = $this->makeTemplate();
        $dto = new WorkflowStageDto(
            name: 'Branch Scoped',
            displayOrder: 1,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
            scopeToBranch: true,
        );

        $stage = $this->makeService()->create($template, $dto);

        $this->assertTrue($stage->scope_to_branch);
    }

    // ── update() ─────────────────────────────────────────────────────────────

    public function test_update_changes_stage_name(): void
    {
        $template = $this->makeTemplate();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $dto = new WorkflowStageDto(
            name: 'Updated Stage Name',
            displayOrder: $stage->display_order,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
        );

        $this->makeService()->update($stage, $dto);

        $this->assertSame('Updated Stage Name', $stage->fresh()->name);
    }

    public function test_update_changes_display_order(): void
    {
        $template = $this->makeTemplate();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $dto = new WorkflowStageDto(
            name: $stage->name,
            displayOrder: 5,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
        );

        $this->makeService()->update($stage, $dto);

        $this->assertSame(5, $stage->fresh()->display_order);
    }

    public function test_update_syncs_roles_replacing_old_ones(): void
    {
        $template = $this->makeTemplate();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $stage->roles()->sync([$this->role->id]);

        $newRole = \App\Models\Role::create(['name' => 'new_role_' . \Illuminate\Support\Str::uuid(), 'guard_name' => 'web']);
        $dto = new WorkflowStageDto(
            name: $stage->name,
            displayOrder: $stage->display_order,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [$newRole->id],
        );

        $this->makeService()->update($stage, $dto);

        $roleIds = $stage->fresh()->roles->pluck('id');
        $this->assertTrue($roleIds->contains($newRole->id));
        $this->assertFalse($roleIds->contains($this->role->id));
    }

    public function test_update_syncs_role_from_tenant_test_case(): void
    {
        $template = $this->makeTemplate();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $dto = new WorkflowStageDto(
            name: $stage->name,
            displayOrder: $stage->display_order,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [$this->role->id],
        );

        $this->makeService()->update($stage, $dto);

        $this->assertTrue($stage->fresh()->roles->pluck('id')->contains($this->role->id));
    }

    public function test_update_sets_skip_below_amount(): void
    {
        $template = $this->makeTemplate();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $dto = new WorkflowStageDto(
            name: $stage->name,
            displayOrder: $stage->display_order,
            skipBelowAmount: 1000.00,
            parallelGroupId: null,
            roleIds: [],
        );

        $this->makeService()->update($stage, $dto);

        $this->assertEqualsWithDelta(1000.00, (float) $stage->fresh()->skip_below_amount, 0.01);
    }

    public function test_update_clears_skip_below_amount_when_null(): void
    {
        $template = $this->makeTemplate();
        $stage = WorkflowStage::factory()->withThreshold(500.0)->create(['workflow_template_id' => $template->id]);
        $dto = new WorkflowStageDto(
            name: $stage->name,
            displayOrder: $stage->display_order,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
        );

        $this->makeService()->update($stage, $dto);

        $this->assertNull($stage->fresh()->skip_below_amount);
    }

    public function test_update_sets_scope_to_department(): void
    {
        $template = $this->makeTemplate();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $dto = new WorkflowStageDto(
            name: $stage->name,
            displayOrder: $stage->display_order,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
            scopeToDepartment: true,
        );

        $this->makeService()->update($stage, $dto);

        $this->assertTrue($stage->fresh()->scope_to_department);
    }

    public function test_update_sets_scope_to_branch(): void
    {
        $template = $this->makeTemplate();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $dto = new WorkflowStageDto(
            name: $stage->name,
            displayOrder: $stage->display_order,
            skipBelowAmount: null,
            parallelGroupId: null,
            roleIds: [],
            scopeToBranch: true,
        );

        $this->makeService()->update($stage, $dto);

        $this->assertTrue($stage->fresh()->scope_to_branch);
    }
}
