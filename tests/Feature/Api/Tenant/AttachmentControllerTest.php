<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Models\Tenant\Attachment;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function initForAttachmentController(): void
{
    Storage::fake('local');
    test()->staff = Staff::factory()->create(['user_id' => test()->user->id, 'branch_id' => test()->branch->id]);
    test()->currency = Currency::factory()->create();
    test()->paymentRequest = PaymentRequest::factory()->create([
        'staff_id' => test()->staff->id,
        'branch_id' => test()->branch->id,
        'currency_id' => test()->currency->id,
    ]);
}
beforeEach(function () {
    initForAttachmentController();
});
test('store for payment request uploads file', function () {
    $file = UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf');

    $this->postJson("/api/payment-requests/{$this->paymentRequest->id}/attachments", [
        'file' => $file,
    ])->assertCreated()
        ->assertJsonStructure(['data' => ['id', 'original_name']]);
});
test('store for payment request requires file', function () {
    $this->postJson("/api/payment-requests/{$this->paymentRequest->id}/attachments", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['file']);
});
test('store for payment request rejects out of scope branch', function () {
    $otherBranch = App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
    $otherPr = PaymentRequest::factory()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $otherBranch->id,
        'currency_id' => $this->currency->id,
    ]);

    $file = UploadedFile::fake()->create('doc.pdf', 50);

    $this->postJson("/api/payment-requests/{$otherPr->id}/attachments", [
        'file' => $file,
    ])->assertForbidden();
});
test('store for retirement request uploads file', function () {
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
});
test('destroy deletes own attachment', function () {
    $attachment = Attachment::factory()->create([
        'attachable_type' => PaymentRequest::class,
        'attachable_id' => $this->paymentRequest->id,
        'user_id' => $this->user->id,
    ]);

    $this->deleteJson("/api/attachments/{$attachment->id}")->assertNoContent();
});
test('destroy forbids deleting others attachment', function () {
    $otherUser = App\Models\Tenant\User::factory()->create(['branch_id' => $this->branch->id]);
    $attachment = Attachment::factory()->create([
        'attachable_type' => PaymentRequest::class,
        'attachable_id' => $this->paymentRequest->id,
        'user_id' => $otherUser->id,
    ]);

    $this->deleteJson("/api/attachments/{$attachment->id}")->assertForbidden();
});
