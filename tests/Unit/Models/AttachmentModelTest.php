<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use Tests\TenantAppTestCase;

class AttachmentModelTest extends TenantAppTestCase
{
    public function test_formatted_size_returns_bytes_for_small_files(): void
    {
        $attachment = Attachment::factory()->create(['size' => 512]);

        $this->assertSame('512 B', $attachment->formattedSize());
    }

    public function test_formatted_size_returns_kb_for_medium_files(): void
    {
        $attachment = Attachment::factory()->create(['size' => 2048]);

        $this->assertSame('2 KB', $attachment->formattedSize());
    }

    public function test_formatted_size_returns_mb_for_large_files(): void
    {
        $attachment = Attachment::factory()->create(['size' => 2097152]);

        $this->assertSame('2 MB', $attachment->formattedSize());
    }

    public function test_attachable_relation_links_to_payment_request(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        $attachment = Attachment::factory()->create([
            'attachable_type' => PaymentRequest::class,
            'attachable_id' => $paymentRequest->id,
        ]);

        $this->assertInstanceOf(PaymentRequest::class, $attachment->attachable);
        $this->assertEquals($paymentRequest->id, $attachment->attachable->id);
    }

    public function test_user_relation_links_to_uploader(): void
    {
        $attachment = Attachment::factory()->create(['user_id' => $this->user->id]);

        $this->assertEquals($this->user->id, $attachment->user->id);
    }
}
