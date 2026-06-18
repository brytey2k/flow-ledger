<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Tenant\PaymentRequestType;
use PHPUnit\Framework\TestCase;

class PaymentRequestTypeTest extends TestCase
{
    public function test_cases_returns_all_three_types(): void
    {
        $this->assertCount(3, PaymentRequestType::cases());
    }

    public function test_advance_value(): void
    {
        $this->assertSame('advance', PaymentRequestType::Advance->value);
    }

    public function test_expense_value(): void
    {
        $this->assertSame('expense', PaymentRequestType::Expense->value);
    }

    public function test_retirement_value(): void
    {
        $this->assertSame('retirement', PaymentRequestType::Retirement->value);
    }

    public function test_can_create_advance_from_string(): void
    {
        $this->assertSame(PaymentRequestType::Advance, PaymentRequestType::from('advance'));
    }

    public function test_can_create_expense_from_string(): void
    {
        $this->assertSame(PaymentRequestType::Expense, PaymentRequestType::from('expense'));
    }

    public function test_can_create_retirement_from_string(): void
    {
        $this->assertSame(PaymentRequestType::Retirement, PaymentRequestType::from('retirement'));
    }

    public function test_try_from_returns_null_for_unknown_value(): void
    {
        $this->assertNull(PaymentRequestType::tryFrom('unknown'));
    }
}
