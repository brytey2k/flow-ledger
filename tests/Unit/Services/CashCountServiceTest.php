<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\CashCountDto;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\CashCountItem;
use App\Models\Tenant\Currency;
use App\Models\Tenant\CurrencyDenomination;
use App\Services\CashCountService;
use Tests\TenantAppTestCase;

class CashCountServiceTest extends TenantAppTestCase
{
    private CashCountService $service;

    private Currency $currency;

    private Cashbook $cashbook;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(CashCountService::class);

        $this->currency = Currency::factory()->create();

        $this->cashbook = Cashbook::create([
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'balance' => '1000.00',
        ]);
    }

    // ── store ─────────────────────────────────────────────────────────────────

    public function test_store_returns_cash_count_instance(): void
    {
        $denomination = $this->makeDenomination('10.00', '10');
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [['denomination_id' => $denomination->id, 'quantity' => 5]],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertInstanceOf(CashCount::class, $cashCount);
    }

    public function test_store_persists_cash_count_to_database(): void
    {
        $denomination = $this->makeDenomination('10.00', '10');
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [['denomination_id' => $denomination->id, 'quantity' => 5]],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertNotNull(CashCount::find($cashCount->id));
    }

    public function test_store_calculates_counted_total_correctly(): void
    {
        // 3 × 10.00 + 2 × 50.00 = 130.00
        $ten = $this->makeDenomination('10.00', '10');
        $fifty = $this->makeDenomination('50.00', '50');
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [
                ['denomination_id' => $ten->id, 'quantity' => 3],
                ['denomination_id' => $fifty->id, 'quantity' => 2],
            ],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertEqualsWithDelta(130.00, (float) $cashCount->counted_total, 0.001);
    }

    public function test_store_computes_difference_as_counted_total_minus_balance(): void
    {
        // balance = 1000.00, counted = 5 × 10.00 = 50.00 → difference = -950.00
        $denomination = $this->makeDenomination('10.00', '10');
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [['denomination_id' => $denomination->id, 'quantity' => 5]],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertEqualsWithDelta(-950.00, (float) $cashCount->difference, 0.001);
    }

    public function test_store_creates_cash_count_item_records_for_each_denomination(): void
    {
        $ten = $this->makeDenomination('10.00', '10');
        $fifty = $this->makeDenomination('50.00', '50');
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [
                ['denomination_id' => $ten->id, 'quantity' => 2],
                ['denomination_id' => $fifty->id, 'quantity' => 3],
            ],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $items = CashCountItem::where('cash_count_id', $cashCount->id)->get();
        $this->assertCount(2, $items);
    }

    public function test_store_cash_count_item_has_correct_subtotal(): void
    {
        // 4 × 25.00 = 100.00
        $denomination = $this->makeDenomination('25.00', '25');
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [['denomination_id' => $denomination->id, 'quantity' => 4]],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $item = CashCountItem::where('cash_count_id', $cashCount->id)->first();
        $this->assertNotNull($item);
        $this->assertEqualsWithDelta(100.00, (float) $item->subtotal, 0.001);
    }

    public function test_store_records_cashbook_balance_at_count(): void
    {
        $denomination = $this->makeDenomination('10.00', '10');
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [['denomination_id' => $denomination->id, 'quantity' => 1]],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertEqualsWithDelta(1000.00, (float) $cashCount->cashbook_balance_at_count, 0.001);
    }

    public function test_store_links_counted_by_user(): void
    {
        $denomination = $this->makeDenomination('10.00', '10');
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [['denomination_id' => $denomination->id, 'quantity' => 1]],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertSame($this->user->id, $cashCount->counted_by_user_id);
    }

    public function test_store_saves_notes(): void
    {
        $denomination = $this->makeDenomination('10.00', '10');
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: 'End of day count',
            items: [['denomination_id' => $denomination->id, 'quantity' => 1]],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertSame('End of day count', $cashCount->notes);
    }

    public function test_store_with_empty_items_sets_counted_total_to_zero(): void
    {
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertEqualsWithDelta(0.00, (float) $cashCount->counted_total, 0.001);
    }

    public function test_store_with_empty_items_sets_difference_to_negative_balance(): void
    {
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertEqualsWithDelta(-1000.00, (float) $cashCount->difference, 0.001);
    }

    public function test_store_with_empty_items_creates_no_cash_count_item_records(): void
    {
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $count = CashCountItem::where('cash_count_id', $cashCount->id)->count();
        $this->assertSame(0, $count);
    }

    public function test_store_skips_items_with_unknown_denomination_id(): void
    {
        $dto = new CashCountDto(
            cashbookId: $this->cashbook->id,
            notes: null,
            items: [['denomination_id' => 999999, 'quantity' => 10]],
        );

        $cashCount = $this->service->store($this->cashbook, $dto, $this->user);

        $this->assertEqualsWithDelta(0.00, (float) $cashCount->counted_total, 0.001);
        $count = CashCountItem::where('cash_count_id', $cashCount->id)->count();
        $this->assertSame(0, $count);
    }

    // ── delete ────────────────────────────────────────────────────────────────

    public function test_delete_removes_cash_count(): void
    {
        $cashCount = $this->makeCashCount();
        $cashCountId = $cashCount->id;

        $this->service->delete($cashCount, $this->user);

        $this->assertNull(CashCount::find($cashCountId));
    }

    public function test_delete_soft_deletes_the_record(): void
    {
        $cashCount = $this->makeCashCount();
        $cashCountId = $cashCount->id;

        $this->service->delete($cashCount, $this->user);

        $this->assertNotNull(CashCount::withTrashed()->find($cashCountId));
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeCashCount(): CashCount
    {
        return CashCount::create([
            'cashbook_id' => $this->cashbook->id,
            'counted_by_user_id' => $this->user->id,
            'counted_at' => now(),
            'cashbook_balance_at_count' => '1000.00',
            'counted_total' => '1000.00',
            'difference' => '0.00',
            'notes' => null,
        ]);
    }

    private function makeDenomination(string $value, string $label, int $sortOrder = 10): CurrencyDenomination
    {
        return CurrencyDenomination::create([
            'currency_id' => $this->currency->id,
            'value' => $value,
            'label' => $label,
            'type' => 'note',
            'sort_order' => $sortOrder,
        ]);
    }
}
