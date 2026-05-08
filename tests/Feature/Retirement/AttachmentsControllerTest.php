<?php

declare(strict_types=1);

namespace Tests\Feature\Retirement;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantAppTestCase;

class AttachmentsControllerTest extends TenantAppTestCase
{
    private function draftRetirement(): RetirementRequest
    {
        $advance = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);

        return RetirementRequest::factory()->create([
            'payment_request_id' => $advance->id,
            'status' => 'draft',
        ]);
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

    public function test_user_without_permission_cannot_delete(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DeleteAttachment->value);
        $retirement = $this->draftRetirement();
        $attachment = Attachment::factory()->create([
            'attachable_type' => RetirementRequest::class,
            'attachable_id' => $retirement->id,
            'user_id' => $this->user->id,
            'path' => 'retirements/1/attachments/test.pdf',
        ]);

        $this->actingAs($this->user)
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
