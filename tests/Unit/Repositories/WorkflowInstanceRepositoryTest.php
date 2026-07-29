<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Role;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Repositories\WorkflowInstanceRepository;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->repository = app(WorkflowInstanceRepository::class);
});
function makeActiveInstanceStageForRole(Role $role): WorkflowInstanceStage
{
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $stage->roles()->attach($role->id);

    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'in_workflow',
        'branch_id' => test()->branch->id,
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
test('active stages for user returns paginator', function () {
    $result = $this->repository->activeStagesForUser($this->user);

    expect($result)->toBeInstanceOf(Illuminate\Contracts\Pagination\LengthAwarePaginator::class);
});
test('active stages for user returns stages matching user role', function () {
    makeActiveInstanceStageForRole($this->role);

    $result = $this->repository->activeStagesForUser($this->user);

    expect($result->total())->toBeGreaterThan(0);
});
test('active stages for user excludes stages not matching user role', function () {
    $otherRole = Role::create(['name' => 'other_role_' . Str::uuid(), 'guard_name' => 'web']);
    makeActiveInstanceStageForRole($otherRole);

    $newUser = App\Models\Tenant\User::factory()->create(['branch_id' => $this->branch->id, 'operational_branch_id' => $this->branch->id]);
    $newRole = Role::create(['name' => 'new_role_' . Str::uuid(), 'guard_name' => 'web']);
    $newUser->assignRole($newRole);

    $result = $this->repository->activeStagesForUser($newUser);

    expect($result->total())->toBe(0);
});
test('active stages for user excludes non active stages', function () {
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

    expect($result->total())->toBe(0);
});
test('active stages for user respects per page', function () {
    $result = $this->repository->activeStagesForUser($this->user, 5);

    expect($result->perPage())->toBe(5);
});
test('approval turnaround stages returns collection', function () {
    $dateFrom = now()->startOfDay()->toDateTimeString();
    $dateTo = now()->endOfDay()->toDateTimeString();

    $result = $this->repository->approvalTurnaroundStages($dateFrom, $dateTo);

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('approval turnaround stages returns completed stages in range', function () {
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

    expect($result->count())->toBeGreaterThan(0);
});
test('approval turnaround stages excludes stages without completed at', function () {
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

    expect($result->count())->toBe(0);
});
test('retirement turnaround stages returns collection', function () {
    $dateFrom = now()->startOfDay()->toDateTimeString();
    $dateTo = now()->endOfDay()->toDateTimeString();

    $result = $this->repository->retirementTurnaroundStages($dateFrom, $dateTo);

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('retirement turnaround stages only returns retirement stages', function () {
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
        expect($instanceStage->instance->workflowable_type)->toBe(RetirementRequest::class);
    }
});
test('active request stages returns collection', function () {
    $result = $this->repository->activeRequestStages();

    expect($result)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('active request stages returns only active stages with started at', function () {
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

    expect($result->count())->toBeGreaterThan(0);
    foreach ($result as $instanceStage) {
        expect($instanceStage->status)->toBe('active');
        expect($instanceStage->started_at)->not->toBeNull();
    }
});
test('active request stages excludes pending stages', function () {
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
});
