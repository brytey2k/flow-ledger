<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\Tenant\CurrencyDenominationType;
use PHPUnit\Framework\TestCase;

class CurrencyDenominationTypeTest extends TestCase
{
    public function test_cases_returns_two_types(): void
    {
        $this->assertCount(2, CurrencyDenominationType::cases());
    }

    public function test_note_value(): void
    {
        $this->assertSame('note', CurrencyDenominationType::Note->value);
    }

    public function test_coin_value(): void
    {
        $this->assertSame('coin', CurrencyDenominationType::Coin->value);
    }

    public function test_note_label(): void
    {
        $this->assertSame('Note', CurrencyDenominationType::Note->label());
    }

    public function test_coin_label(): void
    {
        $this->assertSame('Coin', CurrencyDenominationType::Coin->label());
    }

    public function test_can_create_note_from_string(): void
    {
        $this->assertSame(CurrencyDenominationType::Note, CurrencyDenominationType::from('note'));
    }

    public function test_can_create_coin_from_string(): void
    {
        $this->assertSame(CurrencyDenominationType::Coin, CurrencyDenominationType::from('coin'));
    }

    public function test_try_from_returns_null_for_unknown_value(): void
    {
        $this->assertNull(CurrencyDenominationType::tryFrom('unknown'));
    }
}
