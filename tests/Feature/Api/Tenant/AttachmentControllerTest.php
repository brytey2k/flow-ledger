<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Models\Tenant\Attachment;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\ApiTenantTestCase;

class AttachmentControllerTest extends ApiTenantTestCase
{
    private Staff $staff;
    private Currency $currency;
    private PaymentRequest $paymentRequest;

    protected function init(): void
    {
        parent::init();
        Storage::fake('local');
        $this->staff = Staff::factory()->create(['user_id' => $this->user->id, 'branch_id' => $this->branch->id]);
        $this->currency = Currency::factory()->create();
        $this->paymentRequest = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
        ]);
    }

    // ── Store for PaymentRequest ───────────────────────────────────────────────

    public function test_store_for_payment_request_uploads_file(): void
    {
        $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

        $this->postJson("/api/payment-requests/{$this->paymentRequest->id}/attachments", [
            'file' => $file,
        ])->assertCreated()
            ->assertJsonStructure(['data' => ['id', 'original_name']]);
    }

    public function test_store_for_payment_request_requires_file(): void
    {
        $this->postJson("/api/payment-requests/{$this->paymentRequest->id}/attachments", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file']);
    }

    public function test_store_for_payment_request_rejects_out_of_scope_branch(): void
    {
        $otherBranch = \App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
        $otherPr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $otherBranch->id,
            'currency_id' => $this->currency->id,
        ]);

        $file = UploadedFile::fake()->create('doc.pdf', 50);

        $this->postJson("/api/payment-requests/{$otherPr->id}/attachments", [
            'file' => $file,
        ])->assertForbidden();
    }

    // ── Store for RetirementRequest ───────────────────────────────────────────

    public function test_store_for_retirement_request_uploads_file(): void
    {
        $disbursedPr = PaymentRequest::factory()->advance()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'disbursed',
        ]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $disbursedPr->id,
        ]);

        $file = UploadedFile::fake()->create('receipt.jpg', 100, 'image/jpeg');

        $this->postJson("/api/retirement-requests/{$retirement->id}/attachments", [
            'file' => $file,
        ])->assertCreated();
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_deletes_own_attachment(): void
    {
        $attachment = Attachment::factory()->create([
            'attachable_type' => PaymentRequest::class,
            'attachable_id' => $this->paymentRequest->id,
            'user_id' => $this->user->id,
        ]);

        $this->deleteJson("/api/attachments/{$attachment->id}")->assertNoContent();
    }

    public function test_destroy_forbids_deleting_others_attachment(): void
    {
        $otherUser = \App\Models\Tenant\User::factory()->create(['branch_id' => $this->branch->id]);
        $attachment = Attachment::factory()->create([
            'attachable_type' => PaymentRequest::class,
            'attachable_id' => $this->paymentRequest->id,
            'user_id' => $otherUser->id,
        ]);

        $this->deleteJson("/api/attachments/{$attachment->id}")->assertForbidden();
    }
}
