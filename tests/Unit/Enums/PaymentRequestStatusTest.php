<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Tenant\PaymentRequestStatus;
use PHPUnit\Framework\TestCase;

class PaymentRequestStatusTest extends TestCase
{
    // ── isCancelable ──────────────────────────────────────────────────────────

    public function test_draft_is_cancelable(): void
    {
        $this->assertTrue(PaymentRequestStatus::Draft->isCancelable());
    }

    public function test_in_workflow_is_cancelable(): void
    {
        $this->assertTrue(PaymentRequestStatus::InWorkflow->isCancelable());
    }

    public function test_approved_is_cancelable(): void
    {
        $this->assertTrue(PaymentRequestStatus::Approved->isCancelable());
    }

    public function test_sent_back_is_cancelable(): void
    {
        $this->assertTrue(PaymentRequestStatus::SentBack->isCancelable());
    }

    public function test_disbursed_is_not_cancelable(): void
    {
        $this->assertFalse(PaymentRequestStatus::Disbursed->isCancelable());
    }

    public function test_retired_is_not_cancelable(): void
    {
        $this->assertFalse(PaymentRequestStatus::Retired->isCancelable());
    }

    public function test_cancelled_is_not_cancelable(): void
    {
        $this->assertFalse(PaymentRequestStatus::Cancelled->isCancelable());
    }

    public function test_denied_is_not_cancelable(): void
    {
        $this->assertFalse(PaymentRequestStatus::Denied->isCancelable());
    }

    // ── label ─────────────────────────────────────────────────────────────────

    public function test_draft_label(): void
    {
        $this->assertSame('Draft', PaymentRequestStatus::Draft->label());
    }

    public function test_in_workflow_label(): void
    {
        $this->assertSame('In Workflow', PaymentRequestStatus::InWorkflow->label());
    }

    public function test_approved_label(): void
    {
        $this->assertSame('Approved', PaymentRequestStatus::Approved->label());
    }

    public function test_disbursed_label(): void
    {
        $this->assertSame('Disbursed', PaymentRequestStatus::Disbursed->label());
    }

    public function test_retired_label(): void
    {
        $this->assertSame('Retired', PaymentRequestStatus::Retired->label());
    }

    public function test_cancelled_label(): void
    {
        $this->assertSame('Cancelled', PaymentRequestStatus::Cancelled->label());
    }

    public function test_denied_label(): void
    {
        $this->assertSame('Denied', PaymentRequestStatus::Denied->label());
    }

    public function test_sent_back_label(): void
    {
        $this->assertSame('Sent Back', PaymentRequestStatus::SentBack->label());
    }

    // ── values ────────────────────────────────────────────────────────────────

    public function test_cases_returns_all_eight_statuses(): void
    {
        $this->assertCount(8, PaymentRequestStatus::cases());
    }

    public function test_can_be_created_from_string_value(): void
    {
        $this->assertSame(PaymentRequestStatus::Draft, PaymentRequestStatus::from('draft'));
        $this->assertSame(PaymentRequestStatus::InWorkflow, PaymentRequestStatus::from('in_workflow'));
        $this->assertSame(PaymentRequestStatus::Approved, PaymentRequestStatus::from('approved'));
        $this->assertSame(PaymentRequestStatus::Disbursed, PaymentRequestStatus::from('disbursed'));
        $this->assertSame(PaymentRequestStatus::Retired, PaymentRequestStatus::from('retired'));
        $this->assertSame(PaymentRequestStatus::Cancelled, PaymentRequestStatus::from('cancelled'));
        $this->assertSame(PaymentRequestStatus::Denied, PaymentRequestStatus::from('denied'));
        $this->assertSame(PaymentRequestStatus::SentBack, PaymentRequestStatus::from('sent_back'));
    }
}
