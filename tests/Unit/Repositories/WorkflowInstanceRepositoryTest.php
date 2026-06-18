<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Role;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\WorkflowInstanceRepository;
use Illuminate\Support\Str;
use Tests\TenantAppTestCase;

class WorkflowInstanceRepositoryTest extends TenantAppTestCase
{
    private WorkflowInstanceRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(WorkflowInstanceRepository::class);
    }

    private function makeActiveInstanceStageForRole(Role $role): WorkflowInstanceStage
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $stage->roles()->attach($role->id);

        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'in_workflow',
            'branch_id' => $this->branch->id,
        ]);

        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        return WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'active',
            'started_at' => now(),
            'completed_at' => null,
        ]);
    }

    // ── activeStagesForUser ───────────────────────────────────────────────────

    public function test_active_stages_for_user_returns_paginator(): void
    {
        $result = $this->repository->activeStagesForUser($this->user);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_active_stages_for_user_returns_stages_matching_user_role(): void
    {
        $this->makeActiveInstanceStageForRole($this->role);

        $result = $this->repository->activeStagesForUser($this->user);

        $this->assertGreaterThan(0, $result->total());
    }

    public function test_active_stages_for_user_excludes_stages_not_matching_user_role(): void
    {
        $otherRole = Role::create(['name' => 'other_role_' . Str::uuid(), 'guard_name' => 'web']);
        $this->makeActiveInstanceStageForRole($otherRole);

        $newUser = \App\Models\Tenant\User::factory()->create(['branch_id' => $this->branch->id, 'operational_branch_id' => $this->branch->id]);
        $newRole = Role::create(['name' => 'new_role_' . Str::uuid(), 'guard_name' => 'web']);
        $newUser->assignRole($newRole);

        $result = $this->repository->activeStagesForUser($newUser);

        $this->assertSame(0, $result->total());
    }

    public function test_active_stages_for_user_excludes_non_active_stages(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $stage->roles()->attach($this->role->id);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => $this->branch->id]);

        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
        ]);

        $result = $this->repository->activeStagesForUser($this->user);

        $this->assertSame(0, $result->total());
    }

    public function test_active_stages_for_user_respects_per_page(): void
    {
        $result = $this->repository->activeStagesForUser($this->user, 5);

        $this->assertSame(5, $result->perPage());
    }

    // ── approvalTurnaroundStages ──────────────────────────────────────────────

    public function test_approval_turnaround_stages_returns_collection(): void
    {
        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->approvalTurnaroundStages($dateFrom, $dateTo);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_approval_turnaround_stages_returns_completed_stages_in_range(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'completed',
        ]);

        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'approved',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->approvalTurnaroundStages($dateFrom, $dateTo);

        $this->assertGreaterThan(0, $result->count());
    }

    public function test_approval_turnaround_stages_excludes_stages_without_completed_at(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => $this->branch->id]);

        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'active',
            'started_at' => now()->subHour(),
            'completed_at' => null,
        ]);

        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->approvalTurnaroundStages($dateFrom, $dateTo);

        $this->assertSame(0, $result->count());
    }

    // ── retirementTurnaroundStages ────────────────────────────────────────────

    public function test_retirement_turnaround_stages_returns_collection(): void
    {
        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->retirementTurnaroundStages($dateFrom, $dateTo);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_retirement_turnaround_stages_only_returns_retirement_stages(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);
        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'completed',
        ]);
        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'approved',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->retirementTurnaroundStages($dateFrom, $dateTo);

        foreach ($result as $instanceStage) {
            $this->assertSame(RetirementRequest::class, $instanceStage->instance->workflowable_type);
        }
    }

    // ── activeRequestStages ───────────────────────────────────────────────────

    public function test_active_request_stages_returns_collection(): void
    {
        $result = $this->repository->activeRequestStages();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_active_request_stages_returns_only_active_stages_with_started_at(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => $this->branch->id]);

        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'active',
            'started_at' => now(),
            'completed_at' => null,
        ]);

        $result = $this->repository->activeRequestStages();

        $this->assertGreaterThan(0, $result->count());
        foreach ($result as $instanceStage) {
            $this->assertSame('active', $instanceStage->status);
            $this->assertNotNull($instanceStage->started_at);
        }
    }

    public function test_active_request_stages_excludes_pending_stages(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => $this->branch->id]);

        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'pending',
            'started_at' => null,
            'completed_at' => null,
        ]);

        $result = $this->repository->activeRequestStages();

        foreach ($result as $instanceStage) {
            $this->assertNotSame('pending', $instanceStage->status);
        }
    }
}
