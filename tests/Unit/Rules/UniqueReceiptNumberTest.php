<?php

declare(strict_types=1);

namespace Tests\Unit\Rules;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\PaymentRequestItem;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\RetirementRequestItem;
use App\Rules\Tenant\UniqueReceiptNumber;
use Closure;
use Tests\TenantAppTestCase;

class UniqueReceiptNumberTest extends TenantAppTestCase
{
    private function validate(string $receiptNumber, UniqueReceiptNumber $rule): bool
    {
        $failed = false;
        $rule->validate('receipt_number', $receiptNumber, function () use (&$failed): void {
            $failed = true;
        });

        return ! $failed;
    }

    private function makeClosure(): Closure
    {
        return function (): void {};
    }

    // ── passes ────────────────────────────────────────────────────────────────

    public function test_passes_when_receipt_number_is_unique(): void
    {
        $rule = new UniqueReceiptNumber();

        $this->assertTrue($this->validate('REC-UNIQUE-001', $rule));
    }

    // ── retirement request checks ─────────────────────────────────────────────

    public function test_fails_when_receipt_number_exists_on_non_cancelled_retirement(): void
    {
        $retirement = RetirementRequest::factory()->create(['status' => 'draft']);
        RetirementRequestItem::factory()->create([
            'retirement_request_id' => $retirement->id,
            'receipt_number' => 'REC-001',
        ]);

        $rule = new UniqueReceiptNumber();

        $this->assertFalse($this->validate('REC-001', $rule));
    }

    public function test_passes_when_receipt_number_only_exists_on_cancelled_retirement(): void
    {
        $retirement = RetirementRequest::factory()->create(['status' => 'cancelled']);
        RetirementRequestItem::factory()->create([
            'retirement_request_id' => $retirement->id,
            'receipt_number' => 'REC-CANCELLED',
        ]);

        $rule = new UniqueReceiptNumber();

        $this->assertTrue($this->validate('REC-CANCELLED', $rule));
    }

    public function test_passes_when_excluding_own_retirement_request_id(): void
    {
        $retirement = RetirementRequest::factory()->create(['status' => 'draft']);
        RetirementRequestItem::factory()->create([
            'retirement_request_id' => $retirement->id,
            'receipt_number' => 'REC-EDIT',
        ]);

        $rule = new UniqueReceiptNumber(excludeRetirementRequestId: $retirement->id);

        $this->assertTrue($this->validate('REC-EDIT', $rule));
    }

    public function test_fails_for_other_retirement_even_when_own_is_excluded(): void
    {
        $own = RetirementRequest::factory()->create(['status' => 'draft']);
        RetirementRequestItem::factory()->create([
            'retirement_request_id' => $own->id,
            'receipt_number' => 'REC-SHARED',
        ]);

        $other = RetirementRequest::factory()->create(['status' => 'draft']);
        RetirementRequestItem::factory()->create([
            'retirement_request_id' => $other->id,
            'receipt_number' => 'REC-SHARED',
        ]);

        $rule = new UniqueReceiptNumber(excludeRetirementRequestId: $own->id);

        $this->assertFalse($this->validate('REC-SHARED', $rule));
    }

    // ── payment request checks ────────────────────────────────────────────────

    public function test_fails_when_receipt_number_exists_on_active_payment_request(): void
    {
        $payment = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        PaymentRequestItem::factory()->create([
            'payment_request_id' => $payment->id,
            'receipt_number' => 'REC-PAY-001',
        ]);

        $rule = new UniqueReceiptNumber();

        $this->assertFalse($this->validate('REC-PAY-001', $rule));
    }

    public function test_passes_when_receipt_number_only_exists_on_cancelled_payment_request(): void
    {
        $payment = PaymentRequest::factory()->advance()->create(['status' => 'cancelled']);
        PaymentRequestItem::factory()->create([
            'payment_request_id' => $payment->id,
            'receipt_number' => 'REC-PAY-CANCELLED',
        ]);

        $rule = new UniqueReceiptNumber();

        $this->assertTrue($this->validate('REC-PAY-CANCELLED', $rule));
    }

    public function test_passes_when_receipt_number_only_exists_on_denied_payment_request(): void
    {
        $payment = PaymentRequest::factory()->advance()->create(['status' => 'denied']);
        PaymentRequestItem::factory()->create([
            'payment_request_id' => $payment->id,
            'receipt_number' => 'REC-PAY-DENIED',
        ]);

        $rule = new UniqueReceiptNumber();

        $this->assertTrue($this->validate('REC-PAY-DENIED', $rule));
    }

    public function test_passes_when_excluding_own_payment_request_id(): void
    {
        $payment = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        PaymentRequestItem::factory()->create([
            'payment_request_id' => $payment->id,
            'receipt_number' => 'REC-PAY-EDIT',
        ]);

        $rule = new UniqueReceiptNumber(excludePaymentRequestId: $payment->id);

        $this->assertTrue($this->validate('REC-PAY-EDIT', $rule));
    }

    // ── cross-table checks ────────────────────────────────────────────────────

    public function test_fails_when_receipt_number_exists_on_retirement_even_if_not_on_payment(): void
    {
        $retirement = RetirementRequest::factory()->create(['status' => 'in_workflow']);
        RetirementRequestItem::factory()->create([
            'retirement_request_id' => $retirement->id,
            'receipt_number' => 'REC-CROSS',
        ]);

        $rule = new UniqueReceiptNumber();

        $this->assertFalse($this->validate('REC-CROSS', $rule));
    }

    public function test_fails_when_receipt_number_exists_on_payment_even_if_not_on_retirement(): void
    {
        $payment = PaymentRequest::factory()->advance()->create(['status' => 'approved']);
        PaymentRequestItem::factory()->create([
            'payment_request_id' => $payment->id,
            'receipt_number' => 'REC-CROSS-PAY',
        ]);

        $rule = new UniqueReceiptNumber();

        $this->assertFalse($this->validate('REC-CROSS-PAY', $rule));
    }
}
