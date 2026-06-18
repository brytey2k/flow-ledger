<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\CurrencyDenominationDto;
use App\Enums\Tenant\CurrencyDenominationType;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\CashCountItem;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use App\Services\CurrencyDenominationService;
use Tests\TenantAppTestCase;

class CurrencyDenominationServiceTest extends TenantAppTestCase
{
    private function makeService(): CurrencyDenominationService
    {
        return app(CurrencyDenominationService::class);
    }

    private function makeCurrency(): Currency
    {
        return Currency::factory()->create();
    }

    private function makeDenomination(Currency $currency, string $value = '10.00'): CurrencyDenomination
    {
        return CurrencyDenomination::create([
            'currency_id' => $currency->id,
            'value' => $value,
            'label' => $value,
            'type' => 'note',
            'sort_order' => (int) round((float) $value),
        ]);
    }

    private function makeCashbook(Currency $currency): Cashbook
    {
        return Cashbook::create([
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'balance' => '0.00',
        ]);
    }

    // ── store() ──────────────────────────────────────────────────────────────

    public function test_store_returns_currency_denomination_instance(): void
    {
        $currency = $this->makeCurrency();
        $dto = new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: '50.00',
            label: '50',
            type: CurrencyDenominationType::Note,
            sortOrder: 0,
        );

        $denomination = $this->makeService()->store($dto, $this->user);

        $this->assertInstanceOf(CurrencyDenomination::class, $denomination);
    }

    public function test_store_persists_denomination_with_correct_fields(): void
    {
        $currency = $this->makeCurrency();
        $dto = new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: '50.00',
            label: '50',
            type: CurrencyDenominationType::Note,
            sortOrder: 0,
        );

        $denomination = $this->makeService()->store($dto, $this->user);

        $persisted = CurrencyDenomination::findOrFail($denomination->id);
        $this->assertSame($currency->id, $persisted->currency_id);
        $this->assertSame('50.0000', (string) $persisted->value);
        $this->assertSame('50', $persisted->label);
        $this->assertSame(CurrencyDenominationType::Note, $persisted->type);
    }

    public function test_store_sets_sort_order_as_rounded_integer_of_value(): void
    {
        $currency = $this->makeCurrency();
        $dto = new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: '10.75',
            label: '10',
            type: CurrencyDenominationType::Note,
            sortOrder: 0,
        );

        $denomination = $this->makeService()->store($dto, $this->user);

        $this->assertSame(11, $denomination->sort_order);
    }

    public function test_store_sets_sort_order_rounded_down_for_values_below_half(): void
    {
        $currency = $this->makeCurrency();
        $dto = new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: '20.20',
            label: '20',
            type: CurrencyDenominationType::Coin,
            sortOrder: 0,
        );

        $denomination = $this->makeService()->store($dto, $this->user);

        $this->assertSame(20, $denomination->sort_order);
    }

    public function test_store_supports_coin_type(): void
    {
        $currency = $this->makeCurrency();
        $dto = new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: '0.50',
            label: '50p',
            type: CurrencyDenominationType::Coin,
            sortOrder: 0,
        );

        $denomination = $this->makeService()->store($dto, $this->user);

        $this->assertSame(CurrencyDenominationType::Coin, $denomination->type);
    }

    // ── update() ─────────────────────────────────────────────────────────────

    public function test_update_changes_denomination_label(): void
    {
        $currency = $this->makeCurrency();
        $denomination = $this->makeDenomination($currency, '10.00');
        $dto = new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: '10.00',
            label: 'Ten',
            type: CurrencyDenominationType::Note,
            sortOrder: 10,
        );

        $this->makeService()->update($denomination, $dto, $this->user);

        $this->assertSame('Ten', $denomination->fresh()->label);
    }

    public function test_update_changes_denomination_value(): void
    {
        $currency = $this->makeCurrency();
        $denomination = $this->makeDenomination($currency, '10.00');
        $dto = new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: '20.00',
            label: '20',
            type: CurrencyDenominationType::Note,
            sortOrder: 20,
        );

        $this->makeService()->update($denomination, $dto, $this->user);

        $this->assertSame('20.0000', (string) $denomination->fresh()->value);
    }

    public function test_update_changes_denomination_type(): void
    {
        $currency = $this->makeCurrency();
        $denomination = $this->makeDenomination($currency, '5.00');
        $dto = new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: '5.00',
            label: '5',
            type: CurrencyDenominationType::Coin,
            sortOrder: 5,
        );

        $this->makeService()->update($denomination, $dto, $this->user);

        $this->assertSame(CurrencyDenominationType::Coin, $denomination->fresh()->type);
    }

    public function test_update_changes_denomination_sort_order(): void
    {
        $currency = $this->makeCurrency();
        $denomination = $this->makeDenomination($currency, '10.00');
        $dto = new CurrencyDenominationDto(
            currencyId: $currency->id,
            value: '10.00',
            label: '10',
            type: CurrencyDenominationType::Note,
            sortOrder: 99,
        );

        $this->makeService()->update($denomination, $dto, $this->user);

        $this->assertSame(99, $denomination->fresh()->sort_order);
    }

    // ── delete() ─────────────────────────────────────────────────────────────

    public function test_delete_removes_denomination_when_no_cash_count_items(): void
    {
        $currency = $this->makeCurrency();
        $denomination = $this->makeDenomination($currency, '10.00');
        $denominationId = $denomination->id;

        $this->makeService()->delete($denomination, $this->user);

        $this->assertNull(CurrencyDenomination::find($denominationId));
    }

    public function test_delete_throws_logic_exception_when_denomination_has_cash_count_items(): void
    {
        $currency = $this->makeCurrency();
        $denomination = $this->makeDenomination($currency, '10.00');
        $cashbook = $this->makeCashbook($currency);

        $cashCount = CashCount::create([
            'cashbook_id' => $cashbook->id,
            'counted_by_user_id' => $this->user->id,
            'counted_at' => now(),
            'cashbook_balance_at_count' => '0.00',
            'counted_total' => '20.00',
            'difference' => '20.00',
        ]);

        CashCountItem::create([
            'cash_count_id' => $cashCount->id,
            'denomination_id' => $denomination->id,
            'denomination_value' => '10.00',
            'denomination_label' => '10',
            'quantity' => 2,
            'subtotal' => '20.00',
        ]);

        $this->expectException(\LogicException::class);
        $this->makeService()->delete($denomination, $this->user);
    }

    public function test_delete_does_not_remove_denomination_when_it_has_cash_count_items(): void
    {
        $currency = $this->makeCurrency();
        $denomination = $this->makeDenomination($currency, '10.00');
        $cashbook = $this->makeCashbook($currency);

        $cashCount = CashCount::create([
            'cashbook_id' => $cashbook->id,
            'counted_by_user_id' => $this->user->id,
            'counted_at' => now(),
            'cashbook_balance_at_count' => '0.00',
            'counted_total' => '20.00',
            'difference' => '20.00',
        ]);

        CashCountItem::create([
            'cash_count_id' => $cashCount->id,
            'denomination_id' => $denomination->id,
            'denomination_value' => '10.00',
            'denomination_label' => '10',
            'quantity' => 2,
            'subtotal' => '20.00',
        ]);

        try {
            $this->makeService()->delete($denomination, $this->user);
        } catch (\LogicException) {
            // expected
        }

        $this->assertNotNull(CurrencyDenomination::find($denomination->id));
    }
}
