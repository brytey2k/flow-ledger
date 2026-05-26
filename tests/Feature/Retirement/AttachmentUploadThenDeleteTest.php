<?php

declare(strict_types=1);

namespace Tests\Feature\Retirement;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TenantAppTestCase;

class AttachmentUploadThenDeleteTest extends TenantAppTestCase
{
    private function draftRetirement(): RetirementRequest
    {
        $advance = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);

        return RetirementRequest::factory()->create([
            'payment_request_id' => $advance->id,
            'status' => 'draft',
        ]);
    }

    public function test_user_can_upload_then_delete_attachment(): void
    {
        Storage::fake('local');
        $retirement = $this->draftRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.attachments.store', $retirement), [
                'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('retirement-requests.show', $retirement));

        $attachment = $retirement->fresh()->attachments()->latest()->first();
        $this->assertNotNull($attachment, 'Expected an attachment to exist after upload');

        $this->actingAs($this->user)
            ->delete(route('attachments.destroy', $attachment))
            ->assertRedirect();

        $this->assertSoftDeleted('attachments', ['id' => $attachment->id]);
    }
}
