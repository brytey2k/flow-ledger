<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\WorkflowActionRepository;

beforeEach(function () {
    $this->repository = app(WorkflowActionRepository::class);
});
function makeInstanceStage(): WorkflowInstanceStage
{
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => test()->branch->id]);

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
test('workflow action totals returns collection', function () {
    $dateFrom = now()->startOfDay()->toDateTimeString();
    $dateTo = now()->endOfDay()->toDateTimeString();

    $result = $this->repository->workflowActionTotals($dateFrom, $dateTo);

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('workflow action totals groups by user', function () {
    $instanceStage = makeInstanceStage();

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

    expect($result->toArray())->toHaveKey($this->user->id);
});
test('workflow action totals counts actions correctly', function () {
    $instanceStage = makeInstanceStage();

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
    expect($userRow)->not->toBeNull();
    expect($userRow->total_actions)->toEqual(2);
});
test('workflow action totals excludes actions outside date range', function () {
    $instanceStage = makeInstanceStage();

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
});
test('workflow action sent back totals returns collection', function () {
    $dateFrom = now()->startOfDay()->toDateTimeString();
    $dateTo = now()->endOfDay()->toDateTimeString();

    $result = $this->repository->workflowActionSentBackTotals($dateFrom, $dateTo);

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('workflow action sent back totals counts only sent back actions', function () {
    $instanceStage = makeInstanceStage();

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
    expect($userRow)->not->toBeNull();
    expect($userRow->sent_back_count)->toEqual(1);
});
test('workflow action sent back totals excludes non sent back actions', function () {
    $instanceStage = makeInstanceStage();

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
});
test('audit trail returns paginator', function () {
    $dateFrom = now()->startOfDay()->toDateTimeString();
    $dateTo = now()->endOfDay()->toDateTimeString();

    $result = $this->repository->auditTrail($dateFrom, $dateTo, null);

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('audit trail returns actions within date range', function () {
    $instanceStage = makeInstanceStage();

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

    expect($result->total())->toBeGreaterThan(0);
});
test('audit trail filters by action type', function () {
    $instanceStage = makeInstanceStage();

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
        expect($action->action)->toBe('sent_back');
    }
});
test('audit trail excludes actions outside date range', function () {
    $instanceStage = makeInstanceStage();

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

    expect($result->total())->toBe(0);
});
test('audit trail per page parameter is respected', function () {
    $dateFrom = now()->startOfDay()->toDateTimeString();
    $dateTo = now()->endOfDay()->toDateTimeString();

    $result = $this->repository->auditTrail($dateFrom, $dateTo, null, 10);

    expect($result->perPage())->toBe(10);
});
