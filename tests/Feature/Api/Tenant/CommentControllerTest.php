<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use Tests\ApiTenantTestCase;

class CommentControllerTest extends ApiTenantTestCase
{
    private Staff $staff;
    private Currency $currency;
    private PaymentRequest $paymentRequest;

    protected function init(): void
    {
        parent::init();
        $this->staff = Staff::factory()->create(['user_id' => $this->user->id, 'branch_id' => $this->branch->id]);
        $this->currency = Currency::factory()->create();
        $this->paymentRequest = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
        ]);
    }

    // ── Payment Request Comments ───────────────────────────────────────────────

    public function test_store_for_payment_request_creates_comment(): void
    {
        $this->postJson("/api/payment-requests/{$this->paymentRequest->id}/comments", [
            'body' => 'Please review the attached receipts.',
        ])->assertCreated()
            ->assertJsonPath('data.body', 'Please review the attached receipts.');
    }

    public function test_store_for_payment_request_requires_body(): void
    {
        $this->postJson("/api/payment-requests/{$this->paymentRequest->id}/comments", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['body']);
    }

    // ── Retirement Request Comments ────────────────────────────────────────────

    public function test_store_for_retirement_request_creates_comment(): void
    {
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
    }
}
