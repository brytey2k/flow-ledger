<?php

declare(strict_types=1);

namespace Tests\Feature\Cashbook;

use App\Enums\Tenant\PaymentMethod;
use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use Tests\TenantAppTestCase;

class CashbookAutoEntryTest extends TenantAppTestCase
{
    private function approvedAdvance(float $amount, Branch|null $branch = null): PaymentRequest
    {
        $attrs = ['status' => 'approved', 'total_amount' => $amount];

        if ($branch !== null) {
            $attrs['branch_id'] = $branch->id;
        }

        return PaymentRequest::factory()->advance()->create($attrs);
    }

    private function approvedRetirement(string $differenceType, float $differenceAmount = 50.00): RetirementRequest
    {
        return RetirementRequest::factory()->approved()->create([
            'difference_type' => $differenceType,
            'difference_amount' => $differenceAmount,
        ]);
    }

    // ── Disbursement → cashbook credit ────────────────────────────────────────

    public function test_disbursement_creates_credit_cashbook_entry(): void
    {
        $request = $this->approvedAdvance(500.00);

        $this->actingAs($this->user)
            ->post(route('disbursements.store', $request), ['disbursement_method' => PaymentMethod::Cash->value]);

        $cashbook = Cashbook::where('branch_id', $request->branch_id)->first();
        $this->assertNotNull($cashbook);

        $this->assertDatabaseHas('cashbook_entries', [
            'cashbook_id' => $cashbook->id,
            'type' => 'credit',
            'sourceable_type' => PaymentRequest::class,
            'sourceable_id' => $request->id,
        ]);
    }

    public function test_disbursement_decrements_cashbook_balance(): void
    {
        $request = $this->approvedAdvance(300.00);

        $this->actingAs($this->user)
            ->post(route('disbursements.store', $request), ['disbursement_method' => PaymentMethod::Cash->value]);

        $cashbook = Cashbook::where('branch_id', $request->branch_id)->first();
        $this->assertEqualsWithDelta(-300.00, (float) $cashbook->balance, 0.01);
    }

    public function test_disbursement_auto_creates_cashbook_for_branch(): void
    {
        $request = $this->approvedAdvance(200.00);
        $this->assertNull(Cashbook::where('branch_id', $request->branch_id)->first());

        $this->actingAs($this->user)
            ->post(route('disbursements.store', $request), ['disbursement_method' => PaymentMethod::Cash->value]);

        $this->assertNotNull(Cashbook::where('branch_id', $request->branch_id)->first());
    }

    public function test_second_disbursement_on_same_branch_accumulates_balance(): void
    {
        $currency = Currency::factory()->create();
        $branch = Branch::factory()->create(['currency_id' => $currency->id]);

        $this->actingAs($this->user)
            ->post(route('disbursements.store', $this->approvedAdvance(200.00, $branch)), ['disbursement_method' => PaymentMethod::Cash->value]);

        $this->actingAs($this->user)
            ->post(route('disbursements.store', $this->approvedAdvance(300.00, $branch)), ['disbursement_method' => PaymentMethod::Cash->value]);

        $cashbook = Cashbook::where('branch_id', $branch->id)->first();
        $this->assertEqualsWithDelta(-500.00, (float) $cashbook->balance, 0.01);
        $this->assertSame(2, $cashbook->entries()->count());
    }

    // ── Retirement settlement → cashbook debit / credit ───────────────────────

    public function test_pay_to_staff_retirement_creates_credit_entry(): void
    {
        $retirement = $this->approvedRetirement('pay_to_staff', 75.00);

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement));

        $branchId = $retirement->paymentRequest()->value('branch_id');
        $cashbook = Cashbook::where('branch_id', $branchId)->first();
        $this->assertNotNull($cashbook);

        $this->assertDatabaseHas('cashbook_entries', [
            'cashbook_id' => $cashbook->id,
            'type' => 'credit',
            'sourceable_type' => RetirementRequest::class,
            'sourceable_id' => $retirement->id,
        ]);
    }

    public function test_pay_to_staff_retirement_decrements_cashbook_balance(): void
    {
        $retirement = $this->approvedRetirement('pay_to_staff', 75.00);

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement));

        $branchId = $retirement->paymentRequest()->value('branch_id');
        $cashbook = Cashbook::where('branch_id', $branchId)->first();
        $this->assertEqualsWithDelta(-75.00, (float) $cashbook->balance, 0.01);
    }

    public function test_refund_to_company_retirement_creates_debit_entry(): void
    {
        $retirement = $this->approvedRetirement('refund_to_company', 100.00);

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement));

        $branchId = $retirement->paymentRequest()->value('branch_id');
        $cashbook = Cashbook::where('branch_id', $branchId)->first();
        $this->assertNotNull($cashbook);

        $this->assertDatabaseHas('cashbook_entries', [
            'cashbook_id' => $cashbook->id,
            'type' => 'debit',
            'sourceable_type' => RetirementRequest::class,
            'sourceable_id' => $retirement->id,
        ]);
    }

    public function test_refund_to_company_retirement_increments_cashbook_balance(): void
    {
        $retirement = $this->approvedRetirement('refund_to_company', 100.00);

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement));

        $branchId = $retirement->paymentRequest()->value('branch_id');
        $cashbook = Cashbook::where('branch_id', $branchId)->first();
        $this->assertEqualsWithDelta(100.00, (float) $cashbook->balance, 0.01);
    }

    public function test_nil_difference_retirement_creates_no_cashbook_entry(): void
    {
        $retirement = $this->approvedRetirement('nil', 0.00);

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement));

        $this->assertDatabaseMissing('cashbook_entries', [
            'sourceable_type' => RetirementRequest::class,
            'sourceable_id' => $retirement->id,
        ]);
    }
}
