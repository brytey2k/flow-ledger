<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Services\AttachmentService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

function makeServiceForAttachmentService(): AttachmentService
{
    return app(AttachmentService::class);
}
function makePaymentRequestForAttachmentService(): PaymentRequest
{
    return PaymentRequest::factory()->advance()->create([
        'branch_id' => test()->branch->id,
        'status' => 'draft',
    ]);
}
function makeRetirementRequest(): RetirementRequest
{
    return RetirementRequest::factory()->create();
}
test('store returns attachment instance', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);

    expect($attachment)->toBeInstanceOf(Attachment::class);
});
test('store persists attachment record', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);

    expect(Attachment::find($attachment->id))->not->toBeNull();
});
test('store for payment request saves file in correct path', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);

    $expectedPrefix = "payment-requests/{$paymentRequest->id}/attachments/";
    expect($attachment->path)->toStartWith($expectedPrefix);
    Storage::disk('local')->assertExists($attachment->path);
});
test('store for retirement request saves file in correct path', function () {
    Storage::fake('local');
    $retirementRequest = makeRetirementRequest();
    $file = UploadedFile::fake()->image('receipt.jpg');

    $attachment = makeServiceForAttachmentService()->store($retirementRequest, $file, $this->user);

    $expectedPrefix = "retirements/{$retirementRequest->id}/attachments/";
    expect($attachment->path)->toStartWith($expectedPrefix);
    Storage::disk('local')->assertExists($attachment->path);
});
test('store sets correct original name', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('my-invoice.jpg');

    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);

    expect($attachment->original_name)->toBe('my-invoice.jpg');
});
test('store sets correct mime type', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);

    expect($attachment->mime_type)->not->toBeEmpty();
});
test('store sets correct size', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);

    expect($attachment->size)->toBe($file->getSize());
});
test('store associates attachment with uploader', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);

    expect($attachment->user_id)->toBe($this->user->id);
});
test('store creates polymorphic relation to attachable', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('test.jpg');

    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);

    expect($attachment->attachable_type)->toBe(PaymentRequest::class);
    expect($attachment->attachable_id)->toBe($paymentRequest->id);
});
test('delete removes attachment record', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);
    $attachmentId = $attachment->id;

    makeServiceForAttachmentService()->delete($attachment);

    $deleted = Attachment::withTrashed()->find($attachmentId);
    expect($deleted)->not->toBeNull();
    expect($deleted->deleted_at)->not->toBeNull();
});
test('delete removes file from disk', function () {
    Storage::fake('local');
    $paymentRequest = makePaymentRequestForAttachmentService();
    $file = UploadedFile::fake()->image('test.jpg');
    $attachment = makeServiceForAttachmentService()->store($paymentRequest, $file, $this->user);
    $path = $attachment->path;

    makeServiceForAttachmentService()->delete($attachment);

    Storage::disk('local')->assertMissing($path);
});
