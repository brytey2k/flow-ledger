<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);

use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowDocumentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;

test('api supports opening uploading and submitting an attachment-only window', function () {
    Storage::fake('local');
    $requester = User::factory()->create(['branch_id' => $this->branch->id, 'operational_branch_id' => $this->branch->id]);
    $staff = Staff::factory()->create(['user_id' => $requester->id, 'branch_id' => $this->branch->id]);
    $subject = PaymentRequest::factory()->expense()->inWorkflow()->create([
        'staff_id' => $staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => Currency::factory()->create()->id,
    ]);
    $template = WorkflowTemplate::factory()->expense()->create();
    $stageDefinition = WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $stageDefinition->roles()->sync([$this->role->id]);
    $instance = WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $subject->id,
        'status' => 'in_progress',
        'submitter_user_id' => $requester->id,
        'branch_id' => $this->branch->id,
    ]);
    $stage = WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDefinition->id,
        'status' => 'active',
        'approver_pool' => 'primary',
        'started_at' => now(),
    ]);

    $this->postJson("/api/approvals/{$stage->id}/document-requests", [
        'reason' => 'Upload the signed invoice.',
    ])->assertCreated();

    $documentRequest = WorkflowDocumentRequest::firstOrFail();
    $this->actingAsApiUser($requester)
        ->postJson("/api/document-requests/{$documentRequest->id}/attachments", [
            'file' => UploadedFile::fake()->create('signed-invoice.pdf', 100, 'application/pdf'),
        ])->assertCreated();

    Notification::fake();
    $this->postJson("/api/document-requests/{$documentRequest->id}/submit")
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'submitted');
});
