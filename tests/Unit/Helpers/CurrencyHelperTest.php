<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Helpers\CurrencyHelper;
use PHPUnit\Framework\TestCase;

class CurrencyHelperTest extends TestCase
{
    // ── getSymbol ─────────────────────────────────────────────────────────────

    public function test_get_symbol_returns_ghs_symbol(): void
    {
        $this->assertSame('GH₵', CurrencyHelper::getSymbol('GHS'));
    }

    public function test_get_symbol_returns_usd_symbol(): void
    {
        $this->assertSame('$', CurrencyHelper::getSymbol('USD'));
    }

    public function test_get_symbol_returns_eur_symbol(): void
    {
        $this->assertSame('€', CurrencyHelper::getSymbol('EUR'));
    }

    public function test_get_symbol_returns_code_itself_as_fallback_for_unknown_currency(): void
    {
        $this->assertSame('UNKNOWN', CurrencyHelper::getSymbol('UNKNOWN'));
    }

    public function test_get_symbol_is_case_insensitive(): void
    {
        $this->assertSame('$', CurrencyHelper::getSymbol('usd'));
    }

    // ── format ────────────────────────────────────────────────────────────────

    public function test_format_usd_amount_with_two_decimal_places(): void
    {
        $this->assertSame('$ 1,234.50', CurrencyHelper::format(1234.50, 'USD'));
    }

    public function test_format_ghs_amount_with_two_decimal_places(): void
    {
        $this->assertSame('GH₵ 1,234.50', CurrencyHelper::format(1234.50, 'GHS'));
    }

    public function test_format_usd_amount_with_zero_decimal_places(): void
    {
        $this->assertSame('$ 1,000', CurrencyHelper::format(1000, 'USD', 0));
    }

    // ── wasConverted ──────────────────────────────────────────────────────────

    public function test_was_converted_returns_true_when_currencies_differ(): void
    {
        $this->assertTrue(CurrencyHelper::wasConverted('GHS', 'USD'));
    }

    public function test_was_converted_returns_false_when_currencies_are_the_same(): void
    {
        $this->assertFalse(CurrencyHelper::wasConverted('USD', 'USD'));
    }
}
