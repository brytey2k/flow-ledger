<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;

test('is draft returns true when status is draft', function () {
    $retirement = RetirementRequest::factory()->create(['status' => 'draft']);

    expect($retirement->isDraft())->toBeTrue();
});
test('is draft returns false when status is not draft', function () {
    $retirement = RetirementRequest::factory()->create(['status' => 'in_workflow']);

    expect($retirement->isDraft())->toBeFalse();
});
test('is sent back returns true when status is sent back', function () {
    $retirement = RetirementRequest::factory()->create(['status' => 'sent_back']);

    expect($retirement->isSentBack())->toBeTrue();
});
test('is sent back returns false for other statuses', function () {
    $retirement = RetirementRequest::factory()->create(['status' => 'draft']);

    expect($retirement->isSentBack())->toBeFalse();
});
test('get type attribute returns retirement', function () {
    $retirement = RetirementRequest::factory()->create();

    expect($retirement->getTypeAttribute())->toBe('retirement');
});
test('payment request relation loads correctly', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);
    $retirement = RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id]);

    expect($retirement->paymentRequest->id)->toEqual($paymentRequest->id);
});
test('settled by relation loads user', function () {
    $user = App\Models\Tenant\User::factory()->create();
    $retirement = RetirementRequest::factory()->create(['settled_by_user_id' => $user->id]);

    expect($retirement->settledBy?->id)->toEqual($user->id);
});
test('staff relation resolves through payment request', function () {
    $staff = App\Models\Tenant\Staff::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'staff_id' => $staff->id,
    ]);
    $retirement = RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id]);

    expect($retirement->staff->id)->toEqual($staff->id);
});
test('branch relation resolves through payment request', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);
    $retirement = RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id]);

    expect($retirement->branch)->not->toBeNull();
});
test('currency relation resolves through payment request', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);
    $retirement = RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id]);

    expect($retirement->currency)->not->toBeNull();
});
test('items relation returns has many', function () {
    $retirement = RetirementRequest::factory()->create();

    expect($retirement->items)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
test('workflow instances relation returns morph many', function () {
    $retirement = RetirementRequest::factory()->create();

    expect($retirement->workflowInstances)->toBeInstanceOf(Illuminate\Database\Eloquent\Collection::class);
});
