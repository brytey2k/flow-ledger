<?php

declare(strict_types=1);

namespace Tests\Feature\Cashbook;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashbookEntry;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use Tests\TenantAppTestCase;

class CashbookControllerTest extends TenantAppTestCase
{
    private function cashbookForBranch(Branch|null $branch = null, float $balance = 0): Cashbook
    {
        $branch ??= $this->branch;
        $currency = Currency::factory()->create();

        return Cashbook::create([
            'branch_id' => $branch->id,
            'currency_id' => $currency->id,
            'balance' => $balance,
        ]);
    }

    private function manualEntry(Cashbook $cashbook, float $amount = 100.00): CashbookEntry
    {
        return CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'debit',
            'amount' => $amount,
            'description' => 'Manual receipt',
            'entry_date' => today(),
            'sourceable_type' => null,
            'sourceable_id' => null,
        ]);
    }

    private function autoEntry(Cashbook $cashbook): CashbookEntry
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['branch_id' => $this->branch->id]);

        return CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'credit',
            'amount' => 200.00,
            'description' => 'Payment disbursed',
            'entry_date' => today(),
            'sourceable_type' => PaymentRequest::class,
            'sourceable_id' => $paymentRequest->id,
        ]);
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('cashbook.index', $this->branch))
            ->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('cashbook.create', $this->branch))
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_store_receipt(): void
    {
        $this->post(route('cashbook.store', $this->branch), ['amount' => '100', 'entry_date' => today()->toDateString()])
            ->assertRedirect(route('login'));
    }

    public function test_guest_cannot_delete_entry(): void
    {
        $cashbook = $this->cashbookForBranch();
        $entry = $this->manualEntry($cashbook);

        $this->delete(route('cashbook.destroy', [$this->branch, $entry]))
            ->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_permission_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessCashbook->value);

        $this->actingAs($this->user)
            ->get(route('cashbook.index', $this->branch))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_access_create_form(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCashbookEntry->value);

        $this->actingAs($this->user)
            ->get(route('cashbook.create', $this->branch))
            ->assertForbidden();
    }

    public function test_user_without_create_permission_cannot_store_receipt(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCashbookEntry->value);

        $this->actingAs($this->user)
            ->post(route('cashbook.store', $this->branch), ['amount' => '100', 'entry_date' => today()->toDateString()])
            ->assertForbidden();
    }

    public function test_user_without_delete_permission_cannot_delete_entry(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DeleteCashbookEntry->value);
        $cashbook = $this->cashbookForBranch();
        $entry = $this->manualEntry($cashbook);

        $this->actingAs($this->user)
            ->delete(route('cashbook.destroy', [$this->branch, $entry]))
            ->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_sees_cashbook_index(): void
    {
        $cashbook = $this->cashbookForBranch();
        $this->manualEntry($cashbook, 250.00);

        $this->actingAs($this->user)
            ->get(route('cashbook.index', $this->branch))
            ->assertOk()
            ->assertViewIs('tenant.cashbook.index')
            ->assertViewHas('cashbook')
            ->assertViewHas('entries');
    }

    public function test_cashbook_is_auto_created_on_first_index_visit(): void
    {
        $currency = Currency::factory()->create();
        $this->branch->update(['currency_id' => $currency->id]);

        $this->assertNull(Cashbook::where('branch_id', $this->branch->id)->first());

        $this->actingAs($this->user)
            ->get(route('cashbook.index', $this->branch))
            ->assertOk();

        $this->assertNotNull(Cashbook::where('branch_id', $this->branch->id)->first());
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authorised_user_sees_manual_receipt_form(): void
    {
        $this->cashbookForBranch();

        $this->actingAs($this->user)
            ->get(route('cashbook.create', $this->branch))
            ->assertOk()
            ->assertViewIs('tenant.cashbook.create');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_debit_entry_and_increments_balance(): void
    {
        $cashbook = $this->cashbookForBranch(balance: 500.00);

        $this->actingAs($this->user)
            ->post(route('cashbook.store', $this->branch), [
                'amount' => '150.00',
                'entry_date' => today()->toDateString(),
                'reference' => 'CHQ-001',
                'notes' => 'Bank top-up',
            ]);

        $this->assertDatabaseHas('cashbook_entries', [
            'cashbook_id' => $cashbook->id,
            'type' => 'debit',
            'sourceable_id' => null,
        ]);

        $this->assertEqualsWithDelta(650.00, (float) $cashbook->fresh()->balance, 0.01);
    }

    public function test_store_redirects_to_cashbook_index_on_success(): void
    {
        $this->cashbookForBranch();

        $this->actingAs($this->user)
            ->post(route('cashbook.store', $this->branch), [
                'amount' => '100.00',
                'entry_date' => today()->toDateString(),
            ])
            ->assertRedirect(route('cashbook.index', $this->branch))
            ->assertSessionHas('success');
    }

    public function test_store_reference_and_notes_are_optional(): void
    {
        $cashbook = $this->cashbookForBranch();

        $this->actingAs($this->user)
            ->post(route('cashbook.store', $this->branch), [
                'amount' => '50.00',
                'entry_date' => today()->toDateString(),
            ])
            ->assertRedirect(route('cashbook.index', $this->branch));

        $this->assertDatabaseHas('cashbook_entries', [
            'cashbook_id' => $cashbook->id,
            'reference' => null,
            'notes' => null,
        ]);
    }

    // ── Store validation ──────────────────────────────────────────────────────

    public function test_amount_is_required(): void
    {
        $this->cashbookForBranch();

        $this->actingAs($this->user)
            ->post(route('cashbook.store', $this->branch), [
                'entry_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_amount_must_be_greater_than_zero(): void
    {
        $this->cashbookForBranch();

        $this->actingAs($this->user)
            ->post(route('cashbook.store', $this->branch), [
                'amount' => '0',
                'entry_date' => today()->toDateString(),
            ])
            ->assertSessionHasErrors('amount');
    }

    public function test_entry_date_is_required(): void
    {
        $this->cashbookForBranch();

        $this->actingAs($this->user)
            ->post(route('cashbook.store', $this->branch), [
                'amount' => '100.00',
            ])
            ->assertSessionHasErrors('entry_date');
    }

    public function test_entry_date_cannot_be_in_the_future(): void
    {
        $this->cashbookForBranch();

        $this->actingAs($this->user)
            ->post(route('cashbook.store', $this->branch), [
                'amount' => '100.00',
                'entry_date' => today()->addDay()->toDateString(),
            ])
            ->assertSessionHasErrors('entry_date');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_deletes_manual_entry_and_decrements_balance(): void
    {
        $cashbook = $this->cashbookForBranch(balance: 400.00);
        $entry = $this->manualEntry($cashbook, 150.00);

        $this->actingAs($this->user)
            ->delete(route('cashbook.destroy', [$this->branch, $entry]))
            ->assertRedirect(route('cashbook.index', $this->branch))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('cashbook_entries', ['id' => $entry->id]);
        $this->assertEqualsWithDelta(250.00, (float) $cashbook->fresh()->balance, 0.01);
    }

    public function test_destroy_cannot_delete_auto_generated_entry(): void
    {
        $cashbook = $this->cashbookForBranch();
        $entry = $this->autoEntry($cashbook);

        $this->actingAs($this->user)
            ->delete(route('cashbook.destroy', [$this->branch, $entry]))
            ->assertRedirect(route('cashbook.index', $this->branch))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('cashbook_entries', ['id' => $entry->id, 'deleted_at' => null]);
    }
}
