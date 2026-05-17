<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Tenant\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    public function test_cash_label(): void
    {
        $this->assertSame('Cash', PaymentMethod::Cash->label());
    }

    public function test_bank_transfer_label(): void
    {
        $this->assertSame('Bank Transfer', PaymentMethod::BankTransfer->label());
    }

    public function test_mobile_money_label(): void
    {
        $this->assertSame('Mobile Money', PaymentMethod::MobileMoney->label());
    }

    public function test_cheque_label(): void
    {
        $this->assertSame('Cheque', PaymentMethod::Cheque->label());
    }
}
