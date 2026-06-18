<?php

declare(strict_types=1);

namespace Tests\Unit\Repositories;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\WorkflowActionRepository;
use Tests\TenantAppTestCase;

class WorkflowActionRepositoryTest extends TenantAppTestCase
{
    private WorkflowActionRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = app(WorkflowActionRepository::class);
    }

    private function makeInstanceStage(): WorkflowInstanceStage
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

        return WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'approved',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);
    }

    // ── workflowActionTotals ──────────────────────────────────────────────────

    public function test_workflow_action_totals_returns_collection(): void
    {
        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->workflowActionTotals($dateFrom, $dateTo);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_workflow_action_totals_groups_by_user(): void
    {
        $instanceStage = $this->makeInstanceStage();

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comment' => null,
            'created_at' => now(),
        ]);

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'sent_back',
            'comment' => null,
            'created_at' => now(),
        ]);

        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->workflowActionTotals($dateFrom, $dateTo);

        $this->assertArrayHasKey($this->user->id, $result->toArray());
    }

    public function test_workflow_action_totals_counts_actions_correctly(): void
    {
        $instanceStage = $this->makeInstanceStage();

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comment' => null,
            'created_at' => now(),
        ]);

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'sent_back',
            'comment' => null,
            'created_at' => now(),
        ]);

        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->workflowActionTotals($dateFrom, $dateTo);

        $userRow = $result->get($this->user->id);
        $this->assertNotNull($userRow);
        $this->assertEquals(2, $userRow->total_actions);
    }

    public function test_workflow_action_totals_excludes_actions_outside_date_range(): void
    {
        $instanceStage = $this->makeInstanceStage();

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comment' => null,
            'created_at' => now()->subYear()->startOfYear(),
        ]);

        // Query only the far past — actions created above should not appear in this range
        $dateFrom = now()->subYear()->startOfYear()->subDay()->toDateTimeString();
        $dateTo = now()->subYear()->startOfYear()->subDay()->endOfDay()->toDateTimeString();

        $result = $this->repository->workflowActionTotals($dateFrom, $dateTo);

        $this->assertArrayNotHasKey($this->user->id, $result->toArray());
    }

    // ── workflowActionSentBackTotals ──────────────────────────────────────────

    public function test_workflow_action_sent_back_totals_returns_collection(): void
    {
        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->workflowActionSentBackTotals($dateFrom, $dateTo);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_workflow_action_sent_back_totals_counts_only_sent_back_actions(): void
    {
        $instanceStage = $this->makeInstanceStage();

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comment' => null,
            'created_at' => now(),
        ]);

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'sent_back',
            'comment' => null,
            'created_at' => now(),
        ]);

        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->workflowActionSentBackTotals($dateFrom, $dateTo);

        $userRow = $result->get($this->user->id);
        $this->assertNotNull($userRow);
        $this->assertEquals(1, $userRow->sent_back_count);
    }

    public function test_workflow_action_sent_back_totals_excludes_non_sent_back_actions(): void
    {
        $instanceStage = $this->makeInstanceStage();

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comment' => null,
            'created_at' => now(),
        ]);

        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->workflowActionSentBackTotals($dateFrom, $dateTo);

        $this->assertArrayNotHasKey($this->user->id, $result->toArray());
    }

    // ── auditTrail ────────────────────────────────────────────────────────────

    public function test_audit_trail_returns_paginator(): void
    {
        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->auditTrail($dateFrom, $dateTo, null);

        $this->assertInstanceOf(\Illuminate\Contracts\Pagination\LengthAwarePaginator::class, $result);
    }

    public function test_audit_trail_returns_actions_within_date_range(): void
    {
        $instanceStage = $this->makeInstanceStage();

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comment' => null,
            'created_at' => now(),
        ]);

        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->auditTrail($dateFrom, $dateTo, null);

        $this->assertGreaterThan(0, $result->total());
    }

    public function test_audit_trail_filters_by_action_type(): void
    {
        $instanceStage = $this->makeInstanceStage();

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comment' => null,
            'created_at' => now(),
        ]);

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'sent_back',
            'comment' => null,
            'created_at' => now(),
        ]);

        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->auditTrail($dateFrom, $dateTo, 'sent_back');

        $actions = $result->items();
        foreach ($actions as $action) {
            $this->assertSame('sent_back', $action->action);
        }
    }

    public function test_audit_trail_excludes_actions_outside_date_range(): void
    {
        $instanceStage = $this->makeInstanceStage();

        $pastDate = now()->subYears(10)->startOfYear();

        WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comment' => null,
            'created_at' => $pastDate,
        ]);

        // Query a specific day in the distant past (no actions there)
        $dateFrom = $pastDate->copy()->subDay()->toDateTimeString();
        $dateTo = $pastDate->copy()->subDay()->endOfDay()->toDateTimeString();

        $result = $this->repository->auditTrail($dateFrom, $dateTo, null);

        $this->assertSame(0, $result->total());
    }

    public function test_audit_trail_per_page_parameter_is_respected(): void
    {
        $dateFrom = now()->startOfDay()->toDateTimeString();
        $dateTo = now()->endOfDay()->toDateTimeString();

        $result = $this->repository->auditTrail($dateFrom, $dateTo, null, 10);

        $this->assertSame(10, $result->perPage());
    }
}
