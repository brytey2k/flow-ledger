<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\CostCode;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\RetirementRequestItem;

function makeItem(): RetirementRequestItem
{
    $costCode = CostCode::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
    ]);
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => $paymentRequest->id,
    ]);

    return RetirementRequestItem::create([
        'retirement_request_id' => $retirement->id,
        'cost_code_id' => $costCode->id,
        'description' => 'Test item',
        'amount' => 250.00,
        'receipt_number' => 'RCP-TEST',
    ]);
}
test('retirement request relation loads correctly', function () {
    $item = makeItem();

    expect($item->retirementRequest)->toBeInstanceOf(RetirementRequest::class);
});
test('cost code relation loads correctly', function () {
    $item = makeItem();

    expect($item->costCode)->toBeInstanceOf(CostCode::class);
});
test('attachments relation returns empty by default', function () {
    $item = makeItem();

    expect($item->attachments)->toHaveCount(0);
});
