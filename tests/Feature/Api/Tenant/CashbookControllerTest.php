<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Staff;
use Tests\ApiTenantTestCase;

class CashbookControllerTest extends ApiTenantTestCase
{
    private Staff $staff;
    private Cashbook $cashbook;

    protected function init(): void
    {
        parent::init();
        $this->staff = Staff::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
        ]);
        $currency = Currency::factory()->create();
        $this->cashbook = Cashbook::create([
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'balance' => 5000.00,
        ]);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_cashbook_and_entries(): void
    {
        $this->getJson('/api/cashbook')
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['cashbook', 'entries'],
                'meta' => ['current_page', 'total'],
            ]);
    }

    public function test_index_requires_access_cashbook_permission(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessCashbook->value);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');

        $this->getJson('/api/cashbook')->assertForbidden();
    }

    public function test_index_422_when_no_staff_profile(): void
    {
        $this->staff->delete();

        $this->getJson('/api/cashbook')->assertStatus(422);
    }

    // ── Store Entry ───────────────────────────────────────────────────────────

    public function test_store_entry_creates_manual_receipt(): void
    {
        $this->postJson('/api/cashbook/entries', [
            'amount' => 250.00,
            'entry_date' => today()->toDateString(),
            'reference' => 'RCPT-001',
        ])->assertCreated()
            ->assertJsonPath('data.amount', '250.00');
    }

    public function test_store_entry_requires_amount_and_date(): void
    {
        $this->postJson('/api/cashbook/entries', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['amount', 'entry_date']);
    }

    public function test_store_entry_rejects_future_date(): void
    {
        $this->postJson('/api/cashbook/entries', [
            'amount' => 100.00,
            'entry_date' => today()->addDay()->toDateString(),
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['entry_date']);
    }

    public function test_store_entry_requires_create_cashbook_entry_permission(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateCashbookEntry->value);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');

        $this->postJson('/api/cashbook/entries', [
            'amount' => 100.00,
            'entry_date' => today()->toDateString(),
        ])->assertForbidden();
    }
}
