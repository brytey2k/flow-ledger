<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\CashbookEntryDto;
use App\Events\CashbookBalanceChanged;
use App\Exceptions\InsufficientCashbookBalanceException;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Services\CashbookService;
use Illuminate\Support\Facades\Event;
use Tests\TenantAppTestCase;

class CashbookServiceTest extends TenantAppTestCase
{
    private function makeService(): CashbookService
    {
        return app(CashbookService::class);
    }

    private function makeCashbook(float $balance = 0.0): Cashbook
    {
        $currency = Currency::factory()->create();

        return Cashbook::create([
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'balance' => $balance,
        ]);
    }

    private function approvedAdvance(float $amount = 500.0): PaymentRequest
    {
        return PaymentRequest::factory()->advance()->create([
            'status' => 'approved',
            'branch_id' => $this->branch->id,
            'total_amount' => $amount,
        ]);
    }

    private function approvedRetirement(string $differenceType, float $differenceAmount, float $advanceAmount = 500.0): RetirementRequest
    {
        $advance = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'branch_id' => $this->branch->id,
            'total_amount' => $advanceAmount,
        ]);

        return RetirementRequest::factory()->approved()->create([
            'payment_request_id' => $advance->id,
            'difference_type' => $differenceType,
            'difference_amount' => $differenceAmount,
        ]);
    }

    // ── recordDisbursement ────────────────────────────────────────────────────

    public function test_record_disbursement_creates_credit_entry(): void
    {
        $request = $this->approvedAdvance(300.0);

        $this->makeService()->recordDisbursement($request, $this->user);

        $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
        $this->assertDatabaseHas('cashbook_entries', [
            'cashbook_id' => $cashbook->id,
            'type' => 'credit',
            'amount' => '300.00',
            'sourceable_type' => PaymentRequest::class,
            'sourceable_id' => $request->id,
        ]);
    }

    public function test_record_disbursement_decrements_balance(): void
    {
        $cashbook = $this->makeCashbook(1000.0);
        $request = $this->approvedAdvance(300.0);

        $this->makeService()->recordDisbursement($request, $this->user);

        $this->assertEqualsWithDelta(700.0, (float) $cashbook->fresh()->balance, 0.01);
    }

    public function test_record_disbursement_auto_creates_cashbook_when_none_exists(): void
    {
        $this->assertNull(Cashbook::where('branch_id', $this->branch->id)->first());
        $request = $this->approvedAdvance(200.0);

        $this->makeService()->recordDisbursement($request, $this->user);

        $this->assertNotNull(Cashbook::where('branch_id', $this->branch->id)->first());
    }

    public function test_record_disbursement_allows_disbursement_when_balance_is_zero(): void
    {
        $this->makeCashbook(0.0);
        $request = $this->approvedAdvance(500.0);

        // Zero balance must not block — allows going negative.
        $this->makeService()->recordDisbursement($request, $this->user);

        $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
        $this->assertEqualsWithDelta(-500.0, (float) $cashbook->balance, 0.01);
    }

    public function test_record_disbursement_throws_when_balance_is_positive_but_insufficient(): void
    {
        $this->makeCashbook(100.0);
        $request = $this->approvedAdvance(500.0);

        $this->expectException(InsufficientCashbookBalanceException::class);
        $this->makeService()->recordDisbursement($request, $this->user);
    }

    public function test_record_disbursement_succeeds_when_balance_exactly_covers_amount(): void
    {
        $this->makeCashbook(500.0);
        $request = $this->approvedAdvance(500.0);

        $this->makeService()->recordDisbursement($request, $this->user);

        $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
        $this->assertEqualsWithDelta(0.0, (float) $cashbook->balance, 0.01);
    }

    public function test_record_disbursement_dispatches_balance_changed_event(): void
    {
        Event::fake([CashbookBalanceChanged::class]);
        $this->makeCashbook(1000.0);
        $request = $this->approvedAdvance(300.0);

        $this->makeService()->recordDisbursement($request, $this->user);

        Event::assertDispatched(CashbookBalanceChanged::class);
    }

    // ── recordRetirementSettlement ────────────────────────────────────────────

    public function test_nil_retirement_creates_no_cashbook_entry(): void
    {
        $retirement = $this->approvedRetirement('nil', 0.0);

        $this->makeService()->recordRetirementSettlement($retirement, $this->user);

        $this->assertDatabaseMissing('cashbook_entries', [
            'sourceable_type' => RetirementRequest::class,
            'sourceable_id' => $retirement->id,
        ]);
    }

    public function test_pay_to_staff_retirement_creates_credit_entry_and_decrements_balance(): void
    {
        $this->makeCashbook(1000.0);
        $retirement = $this->approvedRetirement('pay_to_staff', 75.0);

        $this->makeService()->recordRetirementSettlement($retirement, $this->user);

        $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
        $this->assertDatabaseHas('cashbook_entries', [
            'cashbook_id' => $cashbook->id,
            'type' => 'credit',
            'amount' => '75.00',
            'sourceable_type' => RetirementRequest::class,
            'sourceable_id' => $retirement->id,
        ]);
        $this->assertEqualsWithDelta(925.0, (float) $cashbook->balance, 0.01);
    }

    public function test_refund_to_company_retirement_creates_debit_entry_and_increments_balance(): void
    {
        $this->makeCashbook(500.0);
        $retirement = $this->approvedRetirement('refund_to_company', 100.0);

        $this->makeService()->recordRetirementSettlement($retirement, $this->user);

        $cashbook = Cashbook::where('branch_id', $this->branch->id)->firstOrFail();
        $this->assertDatabaseHas('cashbook_entries', [
            'cashbook_id' => $cashbook->id,
            'type' => 'debit',
            'amount' => '100.00',
            'sourceable_type' => RetirementRequest::class,
            'sourceable_id' => $retirement->id,
        ]);
        $this->assertEqualsWithDelta(600.0, (float) $cashbook->balance, 0.01);
    }

    public function test_pay_to_staff_retirement_throws_when_balance_is_positive_but_insufficient(): void
    {
        $this->makeCashbook(50.0);
        $retirement = $this->approvedRetirement('pay_to_staff', 200.0);

        $this->expectException(InsufficientCashbookBalanceException::class);
        $this->makeService()->recordRetirementSettlement($retirement, $this->user);
    }

    public function test_retirement_settlement_dispatches_balance_changed_event(): void
    {
        Event::fake([CashbookBalanceChanged::class]);
        $this->makeCashbook(1000.0);
        $retirement = $this->approvedRetirement('pay_to_staff', 50.0);

        $this->makeService()->recordRetirementSettlement($retirement, $this->user);

        Event::assertDispatched(CashbookBalanceChanged::class);
    }

    // ── recordManualReceipt ───────────────────────────────────────────────────

    public function test_record_manual_receipt_creates_debit_entry_and_increments_balance(): void
    {
        $cashbook = $this->makeCashbook(200.0);
        $dto = new CashbookEntryDto(
            amount: '150.00',
            entryDate: today(),
            reference: 'REF-001',
            notes: 'Test receipt',
        );

        $this->makeService()->recordManualReceipt($cashbook, $dto, $this->user);

        $this->assertDatabaseHas('cashbook_entries', [
            'cashbook_id' => $cashbook->id,
            'type' => 'debit',
            'amount' => '150.00',
            'reference' => 'REF-001',
        ]);
        $this->assertEqualsWithDelta(350.0, (float) $cashbook->fresh()->balance, 0.01);
    }

    public function test_record_manual_receipt_dispatches_balance_changed_event(): void
    {
        Event::fake([CashbookBalanceChanged::class]);
        $cashbook = $this->makeCashbook(0.0);
        $dto = new CashbookEntryDto(amount: '100.00', entryDate: today(), reference: null, notes: null);

        $this->makeService()->recordManualReceipt($cashbook, $dto, $this->user);

        Event::assertDispatched(CashbookBalanceChanged::class);
    }

    // ── deleteManualReceipt ───────────────────────────────────────────────────

    public function test_delete_manual_receipt_decrements_balance_and_removes_entry(): void
    {
        $cashbook = $this->makeCashbook(500.0);
        $entry = CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'debit',
            'amount' => 150.0,
            'description' => 'Manual receipt',
            'entry_date' => today(),
            'sourceable_type' => null,
            'sourceable_id' => null,
        ]);

        $this->makeService()->deleteManualReceipt($entry);

        $this->assertSoftDeleted('cashbook_entries', ['id' => $entry->id]);
        $this->assertEqualsWithDelta(350.0, (float) $cashbook->fresh()->balance, 0.01);
    }

    public function test_delete_manual_receipt_throws_for_auto_generated_entry(): void
    {
        $cashbook = $this->makeCashbook(500.0);
        $request = $this->approvedAdvance(100.0);

        $entry = CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'credit',
            'amount' => 100.0,
            'description' => 'Payment disbursed',
            'entry_date' => today(),
            'sourceable_type' => PaymentRequest::class,
            'sourceable_id' => $request->id,
        ]);

        $this->expectException(\LogicException::class);
        $this->makeService()->deleteManualReceipt($entry);
    }

    public function test_delete_manual_receipt_dispatches_balance_changed_event(): void
    {
        Event::fake([CashbookBalanceChanged::class]);
        $cashbook = $this->makeCashbook(300.0);
        $entry = CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'debit',
            'amount' => 100.0,
            'description' => 'Manual receipt',
            'entry_date' => today(),
            'sourceable_type' => null,
            'sourceable_id' => null,
        ]);

        $this->makeService()->deleteManualReceipt($entry);

        Event::assertDispatched(CashbookBalanceChanged::class);
    }
}
