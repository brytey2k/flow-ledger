<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use Tests\ApiTenantTestCase;

class DisbursementControllerTest extends ApiTenantTestCase
{
    private Staff $staff;
    private Currency $currency;

    protected function init(): void
    {
        parent::init();
        $this->staff = Staff::factory()->create(['user_id' => $this->user->id, 'branch_id' => $this->branch->id]);
        $this->currency = Currency::factory()->create();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_approved_requests(): void
    {
        PaymentRequest::factory()->count(2)->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'approved',
        ]);
        PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'draft',
        ]);

        $this->getJson('/api/disbursements')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_index_requires_disburse_permission(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DisburseRequests->value);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');

        $this->getJson('/api/disbursements')->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_disburses_approved_request(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'approved',
        ]);

        $this->postJson("/api/disbursements/{$pr->id}", [
            'disbursement_method' => 'cash',
            'disbursement_reference' => 'REF-001',
        ])->assertOk()
            ->assertJsonPath('data.status', 'disbursed');
    }

    public function test_store_rejects_non_approved_request(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'draft',
        ]);

        $this->postJson("/api/disbursements/{$pr->id}", [
            'disbursement_method' => 'cash',
        ])->assertStatus(422);
    }

    public function test_store_rejects_out_of_scope_branch(): void
    {
        $otherBranch = \App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $otherBranch->id,
            'currency_id' => $this->currency->id,
            'status' => 'approved',
        ]);

        $this->postJson("/api/disbursements/{$pr->id}", [
            'disbursement_method' => 'cash',
        ])->assertForbidden();
    }

    public function test_store_requires_valid_disbursement_method(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'approved',
        ]);

        $this->postJson("/api/disbursements/{$pr->id}", [
            'disbursement_method' => 'wire_transfer',
        ])->assertUnprocessable();
    }
}
