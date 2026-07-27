<?php

declare(strict_types=1);

namespace Tests\Feature\Retirement;

use App\Enums\Tenant\PermissionKey;
use App\Models\Role;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\WorkflowEngineService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantAppTestCase;

class AttachmentsControllerTest extends TenantAppTestCase
{
    private function draftRetirement(): RetirementRequest
    {
        $staff = Staff::factory()->withUser($this->user)->create(['branch_id' => $this->branch->id]);
        $advance = PaymentRequest::factory()->advance()->create([
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);

        return RetirementRequest::factory()->create([
            'payment_request_id' => $advance->id,
            'status' => 'draft',
        ]);
    }

    private function retirementInWorkflow(): RetirementRequest
    {
        $advance = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);

        return RetirementRequest::factory()->inWorkflow()->create([
            'payment_request_id' => $advance->id,
        ]);
    }

    /** @return array{template: WorkflowTemplate, stage: WorkflowStage, role: Role} */
    private function makeApprovalStage(int $displayOrder = 1): array
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        $role = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => $displayOrder,
        ]);
        $stage->roles()->sync([$role->id]);

        return ['template' => $template, 'stage' => $stage->fresh(), 'role' => $role];
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_cannot_upload(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();

        $this->post(route('retirement-requests.attachments.store', $retirement), [
            'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('login'));
    }

    public function test_guest_cannot_delete(): void
    {
        $retirement = $this->draftRetirement();
        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => 'retirements/1/attachments/test.pdf',
        ]);

        $this->delete(route('attachments.destroy', $attachment))
            ->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_upload(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateRetirementRequest->value);
        Storage::fake('local');
        $retirement = $this->draftRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.attachments.store', $retirement), [
                'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])->assertForbidden();
    }

    public function test_non_owner_cannot_upload(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();
        $otherUser = User::factory()->create(['operational_branch_id' => $this->branch->id]);
        $otherUser->assignRole($this->role);

        $this->actingAs($otherUser)
            ->post(route('retirement-requests.attachments.store', $retirement), [
                'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])->assertForbidden();
    }

    public function test_non_owner_cannot_delete(): void
    {
        $retirement = $this->draftRetirement();
        $otherUser = User::factory()->create();
        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $otherUser->id,
            'path' => 'retirements/1/attachments/test.pdf',
        ]);

        $this->actingAs($this->user)
            ->delete(route('attachments.destroy', $attachment))
            ->assertForbidden();
    }

    public function test_requester_who_is_not_uploader_cannot_delete(): void
    {
        $requester = User::factory()->create();
        $staff = Staff::factory()->withUser($requester)->create();
        $advance = PaymentRequest::factory()->advance()->create([
            'staff_id' => $staff->id,
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $advance->id,
            'status' => 'draft',
        ]);

        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => 'retirements/1/attachments/test.pdf',
        ]);

        $this->actingAs($requester)
            ->delete(route('attachments.destroy', $attachment))
            ->assertForbidden();
    }

    // ── Upload ────────────────────────────────────────────────────────────────

    public function test_user_can_upload_pdf_attachment(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.attachments.store', $retirement), [
                'file' => UploadedFile::fake()->create('receipt.pdf', 500, 'application/pdf'),
            ])
            ->assertRedirect(route('retirement-requests.show', $retirement));

        $this->assertDatabaseHas('attachments', [
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'original_name' => 'receipt.pdf',
            'user_id' => $this->user->id,
        ]);
    }

    public function test_user_can_upload_image_attachment(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.attachments.store', $retirement), [
                'file' => UploadedFile::fake()->image('receipt.jpg'),
            ])
            ->assertRedirect(route('retirement-requests.show', $retirement));

        $this->assertCount(1, $retirement->fresh()->attachments);
    }

    public function test_file_is_required(): void
    {
        $retirement = $this->draftRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.attachments.store', $retirement))
            ->assertSessionHasErrors('file');
    }

    public function test_file_size_limit_enforced(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.attachments.store', $retirement), [
                'file' => UploadedFile::fake()->create('big.pdf', 11000, 'application/pdf'),
            ])
            ->assertSessionHasErrors('file');
    }

    // ── Download ──────────────────────────────────────────────────────────────

    public function test_guest_cannot_download(): void
    {
        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $this->draftRetirement()->id,
            'user_id' => $this->user->id,
            'path' => 'retirements/1/attachments/test.pdf',
            'original_name' => 'test.pdf',
        ]);

        $this->get(route('attachments.download', $attachment))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_download_attachment(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();
        Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');

        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => "retirements/{$retirement->id}/attachments/test.pdf",
            'original_name' => 'test.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->actingAs($this->user)
            ->get(route('attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('test.pdf');
    }

    public function test_download_returns_404_when_file_missing_from_storage(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();

        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => 'retirements/99/attachments/nonexistent.pdf',
            'original_name' => 'nonexistent.pdf',
            'mime_type' => 'application/pdf',
        ]);

        $this->actingAs($this->user)
            ->get(route('attachments.download', $attachment))
            ->assertNotFound();
    }

    public function test_unrelated_user_cannot_download(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();
        Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');

        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => "retirements/{$retirement->id}/attachments/test.pdf",
            'original_name' => 'test.pdf',
        ]);

        $unrelatedUser = User::factory()->create();

        $this->actingAs($unrelatedUser)
            ->get(route('attachments.download', $attachment))
            ->assertForbidden();
    }

    public function test_requester_can_download_attachment_they_did_not_upload(): void
    {
        Storage::fake('local');
        $requester = User::factory()->create();
        $staff = Staff::factory()->withUser($requester)->create();
        $advance = PaymentRequest::factory()->advance()->create([
            'staff_id' => $staff->id,
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $advance->id,
            'status' => 'draft',
        ]);
        Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');

        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => "retirements/{$retirement->id}/attachments/test.pdf",
            'original_name' => 'test.pdf',
        ]);

        $this->actingAs($requester)
            ->get(route('attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('test.pdf');
    }

    public function test_user_eligible_to_act_on_active_stage_can_download(): void
    {
        Storage::fake('local');
        $engine = app(WorkflowEngineService::class);
        $retirement = $this->retirementInWorkflow();

        ['template' => $template, 'role' => $role] = $this->makeApprovalStage();
        $approver = User::factory()->create();
        $approver->assignRole($role);

        $engine->startWorkflow($retirement, $template, $this->user);

        Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');
        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => "retirements/{$retirement->id}/attachments/test.pdf",
            'original_name' => 'test.pdf',
        ]);

        $this->actingAs($approver)
            ->get(route('attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('test.pdf');
    }

    public function test_user_who_approved_earlier_stage_can_still_download(): void
    {
        Storage::fake('local');
        $engine = app(WorkflowEngineService::class);
        $retirement = $this->retirementInWorkflow();

        ['template' => $template, 'role' => $role1] = $this->makeApprovalStage(1);
        $stage2 = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 2,
        ]);
        $role2 = Role::create(['name' => 'approver_' . uniqid(), 'guard_name' => 'web']);
        $stage2->roles()->sync([$role2->id]);

        $firstApprover = User::factory()->create();
        $firstApprover->assignRole($role1);

        $instance = $engine->startWorkflow($retirement, $template, $this->user);
        $activeStage = $instance->instanceStages()->where('status', 'active')->firstOrFail();
        $engine->approve($activeStage, $firstApprover);

        Storage::disk('local')->put("retirements/{$retirement->id}/attachments/test.pdf", 'file content');
        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => "retirements/{$retirement->id}/attachments/test.pdf",
            'original_name' => 'test.pdf',
        ]);

        $this->actingAs($firstApprover)
            ->get(route('attachments.download', $attachment))
            ->assertOk()
            ->assertDownload('test.pdf');
    }

    // ── Delete ────────────────────────────────────────────────────────────────

    public function test_user_can_delete_attachment(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();

        Storage::disk('local')->put('retirements/1/attachments/test.pdf', 'content');

        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => 'retirements/1/attachments/test.pdf',
        ]);

        $this->actingAs($this->user)
            ->delete(route('attachments.destroy', $attachment))
            ->assertRedirect();

        $this->assertSoftDeleted('attachments', ['id' => $attachment->id]);
    }
}
