<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);

use App\Enums\Tenant\WorkflowDocumentRequestStatus;
use App\Models\Role;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowAction;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Notifications\WorkflowDocumentsSubmittedNotification;
use App\Services\WorkflowDocumentRequestService;
use App\Services\WorkflowEngineService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

/** @return array{subject: PaymentRequest, requester: User, approver: User, instance: WorkflowInstance, stage: WorkflowInstanceStage} */
function expenseDocumentWorkflow(): array
{
    $requester = test()->user;
    $staff = Staff::factory()->create(['user_id' => $requester->id, 'branch_id' => test()->branch->id]);
    $subject = PaymentRequest::factory()->expense()->inWorkflow()->create([
        'staff_id' => $staff->id,
        'branch_id' => test()->branch->id,
        'currency_id' => Currency::factory()->create()->id,
    ]);
    $template = WorkflowTemplate::factory()->expense()->create();
    $role = Role::create(['name' => 'document_approver_' . uniqid(), 'guard_name' => 'web']);
    $approver = User::factory()->create(['branch_id' => test()->branch->id, 'operational_branch_id' => test()->branch->id]);
    $approver->assignRole($role);
    $stageDefinition = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 2,
        'allow_document_requests' => true,
    ]);
    $stageDefinition->roles()->sync([$role->id]);
    $instance = WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $subject->id,
        'status' => 'in_progress',
        'submitter_user_id' => $requester->id,
        'branch_id' => test()->branch->id,
    ]);
    $stage = WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDefinition->id,
        'status' => 'active',
        'approver_pool' => 'primary',
        'started_at' => now(),
    ]);

    return compact('subject', 'requester', 'approver', 'instance', 'stage');
}

beforeEach(function () {
    Storage::fake('local');
    $this->documents = app(WorkflowDocumentRequestService::class);
});

test('approver opens one attachment window and claims the stage', function () {
    $workflow = expenseDocumentWorkflow();

    $request = $this->documents->open($workflow['stage'], $workflow['approver'], 'Provide the signed invoice.');

    expect($request->status)->toBe(WorkflowDocumentRequestStatus::AwaitingUploads)
        ->and($request->requester_user_id)->toBe($workflow['requester']->id)
        ->and($workflow['instance']->fresh()->status)->toBe('in_progress')
        ->and($workflow['stage']->fresh()->status)->toBe('active');
    $this->assertDatabaseHas('workflow_instance_actor_claims', [
        'workflow_instance_id' => $workflow['instance']->id,
        'workflow_instance_stage_id' => $workflow['stage']->id,
        'user_id' => $workflow['approver']->id,
    ]);
    $this->assertDatabaseHas('activity_log', ['event' => 'document_request.opened', 'subject_id' => $workflow['subject']->id]);

    $this->documents->open($workflow['stage'], $workflow['approver'], 'Duplicate');
})->throws(ConflictHttpException::class);

test('workflow approval actions are frozen without changing parallel stage state', function () {
    $workflow = expenseDocumentWorkflow();
    $this->documents->open($workflow['stage'], $workflow['approver'], 'Provide evidence.');

    app(WorkflowEngineService::class)->approve($workflow['stage'], $workflow['approver']);
})->throws(ConflictHttpException::class);

test('request owner may upload remove and submit only current window files', function () {
    $workflow = expenseDocumentWorkflow();
    $request = $this->documents->open($workflow['stage'], $workflow['approver'], 'Provide evidence.');
    $existing = Attachment::factory()->create([
        'attachable_type' => PaymentRequest::class,
        'attachable_id' => $workflow['subject']->id,
        'user_id' => $workflow['requester']->id,
        'workflow_document_request_id' => null,
    ]);

    $first = $this->documents->upload($request, UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'), $workflow['requester']);
    $this->documents->deleteAttachment($request, $first, $workflow['requester']);
    $this->assertSoftDeleted($first);
    expect($existing->fresh())->not->toBeNull();

    $final = $this->documents->upload($request, UploadedFile::fake()->create('signed-invoice.pdf', 100, 'application/pdf'), $workflow['requester']);
    Notification::fake();
    $submitted = $this->documents->submit($request, $workflow['requester']);

    expect($submitted->status)->toBe(WorkflowDocumentRequestStatus::Submitted)
        ->and($final->fresh()->workflow_document_request_id)->toBe($request->id);
    Notification::assertSentTo($workflow['approver'], WorkflowDocumentsSubmittedNotification::class);

    $this->documents->deleteAttachment($request, $final, $workflow['requester']);
})->throws(ConflictHttpException::class);

test('only actual earlier approvers can be referred and concern override resumes the same stage', function () {
    $workflow = expenseDocumentWorkflow();
    $priorApprover = User::factory()->create([
        'branch_id' => test()->branch->id,
        'operational_branch_id' => test()->branch->id,
        'status' => App\Enums\Tenant\UserStatus::Active,
    ]);
    $priorDefinition = WorkflowStage::factory()->create([
        'workflow_template_id' => $workflow['instance']->workflow_template_id,
        'display_order' => 1,
    ]);
    $priorStage = WorkflowInstanceStage::create([
        'workflow_instance_id' => $workflow['instance']->id,
        'workflow_stage_id' => $priorDefinition->id,
        'status' => 'approved',
        'started_at' => now()->subDay(),
        'completed_at' => now()->subHour(),
    ]);
    $action = WorkflowAction::create([
        'workflow_instance_stage_id' => $priorStage->id,
        'user_id' => $priorApprover->id,
        'action' => 'approve',
    ]);
    $request = $this->documents->open($workflow['stage'], $workflow['approver'], 'Provide evidence.');
    $this->documents->upload($request, UploadedFile::fake()->create('invoice.pdf', 100, 'application/pdf'), $workflow['requester']);
    Notification::fake();
    $this->documents->submit($request, $workflow['requester']);
    Notification::assertSentTo($workflow['approver'], WorkflowDocumentsSubmittedNotification::class);
    Notification::assertSentTo(
        $priorApprover,
        WorkflowDocumentsSubmittedNotification::class,
        function (WorkflowDocumentsSubmittedNotification $notification) use ($priorApprover): bool {
            $payload = $notification->toArray($priorApprover);

            return $payload['attachment_names'] === ['invoice.pdf']
                && $payload['stage'] !== ''
                && str_contains((string) $payload['request_url'], '/requests/');
        },
    );
    Notification::assertNotSentTo($workflow['requester'], WorkflowDocumentsSubmittedNotification::class);

    $this->documents->createReferrals($request, $workflow['approver'], [$action->id]);
    $referral = $request->referrals()->firstOrFail();
    $this->documents->respond($referral, $priorApprover, 'concern', 'The signature is unclear.');
    $this->documents->resolve($request, $workflow['approver'], 'override', 'Signature verified against the original.');

    expect($request->fresh()->status)->toBe(WorkflowDocumentRequestStatus::Resolved)
        ->and($workflow['stage']->fresh()->status)->toBe('active')
        ->and($workflow['instance']->fresh()->status)->toBe('in_progress');
    $this->assertDatabaseHas('activity_log', ['event' => 'document_request.concern_overridden']);
});

test('submission requires an active new attachment', function () {
    $workflow = expenseDocumentWorkflow();
    $request = $this->documents->open($workflow['stage'], $workflow['approver'], 'Provide evidence.');

    $this->documents->submit($request, $workflow['requester']);
})->throws(Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException::class);
