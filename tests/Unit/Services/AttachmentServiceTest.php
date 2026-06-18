<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantAppTestCase;

class AttachmentServiceTest extends TenantAppTestCase
{
    private function makeService(): AttachmentService
    {
        return app(AttachmentService::class);
    }

    private function makePaymentRequest(): PaymentRequest
    {
        return PaymentRequest::factory()->advance()->create([
            'branch_id' => $this->branch->id,
            'status' => 'draft',
        ]);
    }

    private function makeRetirementRequest(): RetirementRequest
    {
        return RetirementRequest::factory()->create();
    }

    // ── store() ──────────────────────────────────────────────────────────────

    public function test_store_returns_attachment_instance(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('test.jpg');

        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);

        $this->assertInstanceOf(Attachment::class, $attachment);
    }

    public function test_store_persists_attachment_record(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('test.jpg');

        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);

        $this->assertNotNull(Attachment::find($attachment->id));
    }

    public function test_store_for_payment_request_saves_file_in_correct_path(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('test.jpg');

        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);

        $expectedPrefix = "payment-requests/{$paymentRequest->id}/attachments/";
        $this->assertStringStartsWith($expectedPrefix, $attachment->path);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_store_for_retirement_request_saves_file_in_correct_path(): void
    {
        Storage::fake('local');
        $retirementRequest = $this->makeRetirementRequest();
        $file = UploadedFile::fake()->image('receipt.jpg');

        $attachment = $this->makeService()->store($retirementRequest, $file, $this->user);

        $expectedPrefix = "retirements/{$retirementRequest->id}/attachments/";
        $this->assertStringStartsWith($expectedPrefix, $attachment->path);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_store_sets_correct_original_name(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('my-invoice.jpg');

        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);

        $this->assertSame('my-invoice.jpg', $attachment->original_name);
    }

    public function test_store_sets_correct_mime_type(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('test.jpg');

        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);

        $this->assertNotEmpty($attachment->mime_type);
    }

    public function test_store_sets_correct_size(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('test.jpg');

        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);

        $this->assertSame($file->getSize(), $attachment->size);
    }

    public function test_store_associates_attachment_with_uploader(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('test.jpg');

        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);

        $this->assertSame($this->user->id, $attachment->user_id);
    }

    public function test_store_creates_polymorphic_relation_to_attachable(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('test.jpg');

        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);

        $this->assertSame(PaymentRequest::class, $attachment->attachable_type);
        $this->assertSame($paymentRequest->id, $attachment->attachable_id);
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function test_delete_removes_attachment_record(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('test.jpg');
        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);
        $attachmentId = $attachment->id;

        $this->makeService()->delete($attachment);

        $deleted = Attachment::withTrashed()->find($attachmentId);
        $this->assertNotNull($deleted);
        $this->assertNotNull($deleted->deleted_at);
    }

    public function test_delete_removes_file_from_disk(): void
    {
        Storage::fake('local');
        $paymentRequest = $this->makePaymentRequest();
        $file = UploadedFile::fake()->image('test.jpg');
        $attachment = $this->makeService()->store($paymentRequest, $file, $this->user);
        $path = $attachment->path;

        $this->makeService()->delete($attachment);

        Storage::disk('local')->assertMissing($path);
    }
}
