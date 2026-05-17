<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\CostCode;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\RetirementRequestItem;
use Tests\TenantAppTestCase;

class RetirementRequestItemModelTest extends TenantAppTestCase
{
    private function makeItem(): RetirementRequestItem
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

    public function test_retirement_request_relation_loads_correctly(): void
    {
        $item = $this->makeItem();

        $this->assertInstanceOf(RetirementRequest::class, $item->retirementRequest);
    }

    public function test_cost_code_relation_loads_correctly(): void
    {
        $item = $this->makeItem();

        $this->assertInstanceOf(CostCode::class, $item->costCode);
    }

    public function test_attachments_relation_returns_empty_by_default(): void
    {
        $item = $this->makeItem();

        $this->assertCount(0, $item->attachments);
    }
}
