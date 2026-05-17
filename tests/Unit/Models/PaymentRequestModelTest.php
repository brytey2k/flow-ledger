<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\User;
use Tests\TenantAppTestCase;

class PaymentRequestModelTest extends TenantAppTestCase
{
    public function test_is_advance_returns_true_for_advance_type(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $this->assertTrue($request->isAdvance());
    }

    public function test_is_advance_returns_false_for_expense_type(): void
    {
        $request = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

        $this->assertFalse($request->isAdvance());
    }

    public function test_is_expense_returns_true_for_expense_type(): void
    {
        $request = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

        $this->assertTrue($request->isExpense());
    }

    public function test_is_expense_returns_false_for_advance_type(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $this->assertFalse($request->isExpense());
    }

    public function test_is_draft_returns_true_when_status_is_draft(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $this->assertTrue($request->isDraft());
    }

    public function test_is_draft_returns_false_when_status_is_not_draft(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow']);

        $this->assertFalse($request->isDraft());
    }

    public function test_is_sent_back_returns_true_when_status_is_sent_back(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'sent_back']);

        $this->assertTrue($request->isSentBack());
    }

    public function test_is_sent_back_returns_false_when_status_is_not_sent_back(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $this->assertFalse($request->isSentBack());
    }

    public function test_disbursed_by_relation_loads_user(): void
    {
        $user = User::factory()->create();
        $request = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'disbursed_by_user_id' => $user->id,
        ]);

        $this->assertEquals($user->id, $request->disbursedBy?->id);
    }

    public function test_cashbook_entries_relation_returns_morph_many(): void
    {
        $branch = Branch::factory()->create();
        $currency = Currency::factory()->create();
        $request = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);
        $cashbook = Cashbook::create(['branch_id' => $branch->id, 'currency_id' => $currency->id, 'balance' => '0.00']);
        CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'debit',
            'amount' => '100.00',
            'description' => 'Payment',
            'entry_date' => now()->toDateString(),
            'sourceable_type' => PaymentRequest::class,
            'sourceable_id' => $request->id,
        ]);

        $this->assertCount(1, $request->cashbookEntries);
    }
}
