<?php

declare(strict_types=1);

uses(Tests\ApiTenantTestCase::class);
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;

function initForCommentController(): void
{
    test()->staff = Staff::factory()->create(['user_id' => test()->user->id, 'branch_id' => test()->branch->id]);
    test()->currency = Currency::factory()->create();
    test()->paymentRequest = PaymentRequest::factory()->create([
        'staff_id' => test()->staff->id,
        'branch_id' => test()->branch->id,
        'currency_id' => test()->currency->id,
    ]);
}
beforeEach(function () {
    initForCommentController();
});
test('store for payment request creates comment', function () {
    $this->postJson("/api/payment-requests/{$this->paymentRequest->id}/comments", [
        'body' => 'Please review the attached receipts.',
    ])->assertCreated()
        ->assertJsonPath('data.body', 'Please review the attached receipts.');
});
test('store for payment request requires body', function () {
    $this->postJson("/api/payment-requests/{$this->paymentRequest->id}/comments", [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['body']);
});
test('store for retirement request creates comment', function () {
    $disbursedPr = PaymentRequest::factory()->advance()->create([
        'staff_id' => $this->staff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'disbursed',
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $disbursedPr->id,
    ]);

    $this->postJson("/api/retirement-requests/{$retirement->id}/comments", [
        'body' => 'Retirement note.',
    ])->assertCreated()
        ->assertJsonPath('data.body', 'Retirement note.');
});
test('a user who is neither owner nor in the approval chain cannot comment on a payment request', function () {
    $otherStaff = Staff::factory()->create(['branch_id' => $this->branch->id]);
    $strangersRequest = PaymentRequest::factory()->create([
        'staff_id' => $otherStaff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
    ]);

    $this->postJson("/api/payment-requests/{$strangersRequest->id}/comments", ['body' => 'Injected comment'])
        ->assertForbidden();

    expect($strangersRequest->comments()->count())->toBe(0);
});
test('a user who is neither owner nor in the approval chain cannot comment on a retirement request', function () {
    $otherStaff = Staff::factory()->create(['branch_id' => $this->branch->id]);
    $strangersAdvance = PaymentRequest::factory()->advance()->create([
        'staff_id' => $otherStaff->id,
        'branch_id' => $this->branch->id,
        'currency_id' => $this->currency->id,
        'status' => 'disbursed',
    ]);
    $retirement = RetirementRequest::factory()->create(['payment_request_id' => $strangersAdvance->id]);

    $this->postJson("/api/retirement-requests/{$retirement->id}/comments", ['body' => 'Injected comment'])
        ->assertForbidden();
});
