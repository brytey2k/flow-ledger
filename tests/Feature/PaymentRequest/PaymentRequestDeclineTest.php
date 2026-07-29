<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;

test('only approver can decline request', function () {
    $paymentRequest = PaymentRequest::factory()
        ->for($this->branch)
        ->create(['status' => 'in_workflow']);

    $response = $this->actingAs($this->user)->post(
        route('payment-requests.decline', $paymentRequest),
    );

    $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
    $response->assertSessionHas('error', __('flash.requests.no_active_workflow'));

    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBe('in_workflow');
});
test('cannot decline draft request', function () {
    $paymentRequest = PaymentRequest::factory()
        ->for($this->branch)
        ->create(['status' => 'draft']);

    $response = $this->actingAs($this->user)->post(
        route('payment-requests.decline', $paymentRequest),
    );

    $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
    $response->assertSessionHas('error', __('flash.requests.no_active_workflow'));

    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBe('draft');
});
test('user cannot decline request from different branch', function () {
    $paymentRequest = PaymentRequest::factory()
        ->create(['status' => 'in_workflow']);

    $response = $this->actingAs($this->user)->post(
        route('payment-requests.decline', $paymentRequest),
    );

    $response->assertForbidden();
});
test('decline rejects all statuses', function () {
    foreach (['draft', 'approved', 'sent_back', 'disbursed', 'cancelled'] as $status) {
        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create(['status' => $status]);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.decline', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('error');

        $paymentRequest->refresh();
        $this->assertNotSame('denied', $paymentRequest->status);
    }
});
