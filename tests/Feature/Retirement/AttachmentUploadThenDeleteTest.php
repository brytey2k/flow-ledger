<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function draftRetirementForAttachmentUploadThenDelete(): RetirementRequest
{
    $staff = Staff::factory()->withUser(test()->user)->create(['branch_id' => test()->branch->id]);
    $advance = PaymentRequest::factory()->advance()->create([
        'staff_id' => $staff->id,
        'branch_id' => test()->branch->id,
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);

    return RetirementRequest::factory()->create([
        'payment_request_id' => $advance->id,
        'status' => 'draft',
    ]);
}
test('user can upload then delete attachment', function () {
    Storage::fake('local');
    $retirement = draftRetirementForAttachmentUploadThenDelete();

    $this->actingAs($this->user)
        ->post(route('retirement-requests.attachments.store', $retirement), [
            'file' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ])
        ->assertRedirect(route('retirement-requests.show', $retirement));

    $attachment = $retirement->fresh()->attachments()->latest()->first();
    expect($attachment)->not->toBeNull('Expected an attachment to exist after upload');

    $this->actingAs($this->user)
        ->delete(route('attachments.destroy', $attachment))
        ->assertRedirect();

    $this->assertSoftDeleted('attachments', ['id' => $attachment->id]);
});
