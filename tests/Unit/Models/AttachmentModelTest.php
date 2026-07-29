<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Attachment;
use App\Models\Tenant\PaymentRequest;

test('formatted size returns bytes for small files', function () {
    $attachment = Attachment::factory()->create(['size' => 512]);

    expect($attachment->formattedSize())->toBe('512 B');
});
test('formatted size returns kb for medium files', function () {
    $attachment = Attachment::factory()->create(['size' => 2048]);

    expect($attachment->formattedSize())->toBe('2 KB');
});
test('formatted size returns mb for large files', function () {
    $attachment = Attachment::factory()->create(['size' => 2097152]);

    expect($attachment->formattedSize())->toBe('2 MB');
});
test('attachable relation links to payment request', function () {
    $paymentRequest = PaymentRequest::factory()->create();
    $attachment = Attachment::factory()->create([
        'attachable_type' => PaymentRequest::class,
        'attachable_id' => $paymentRequest->id,
    ]);

    expect($attachment->attachable)->toBeInstanceOf(PaymentRequest::class);
    expect($attachment->attachable->id)->toEqual($paymentRequest->id);
});
test('user relation links to uploader', function () {
    $attachment = Attachment::factory()->create(['user_id' => $this->user->id]);

    expect($attachment->user->id)->toEqual($this->user->id);
});
