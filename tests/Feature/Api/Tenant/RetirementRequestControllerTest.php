<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use Tests\ApiTenantTestCase;

class RetirementRequestControllerTest extends ApiTenantTestCase
{
    private Staff $staff;
    private Currency $currency;
    private PaymentRequest $disbursedPr;

    protected function init(): void
    {
        parent::init();
        $this->staff = Staff::factory()->create(['user_id' => $this->user->id, 'branch_id' => $this->branch->id]);
        $this->currency = Currency::factory()->create();
        $this->disbursedPr = PaymentRequest::factory()->advance()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'disbursed',
        ]);
    }

    private function makeDisbursedPr(): PaymentRequest
    {
        return PaymentRequest::factory()->advance()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'disbursed',
        ]);
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_returns_paginated_list(): void
    {
        RetirementRequest::factory()->create(['payment_request_id' => $this->disbursedPr->id]);
        RetirementRequest::factory()->create(['payment_request_id' => $this->makeDisbursedPr()->id]);

        $this->getJson('/api/retirement-requests')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_index_requires_permission(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessRetirementRequests->value);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');

        $this->getJson('/api/retirement-requests')->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_draft_retirement_request(): void
    {
        $costCode = CostCode::factory()->create();

        $this->postJson('/api/retirement-requests', [
            'payment_request_id' => $this->disbursedPr->id,
            'notes' => 'Retirement test',
            'difference_type' => 'nil',
            'did_not_spend_money' => false,
            'items' => [
                ['description' => 'Receipt 1', 'amount' => 100.00, 'cost_code_id' => $costCode->id],
            ],
        ])->assertCreated()
            ->assertJsonPath('data.status', 'draft');
    }

    public function test_store_rejects_non_disbursed_payment_request(): void
    {
        $pr = PaymentRequest::factory()->advance()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'approved',
        ]);
        $costCode = CostCode::factory()->create();

        $this->postJson('/api/retirement-requests', [
            'payment_request_id' => $pr->id,
            'difference_type' => 'nil',
            'did_not_spend_money' => false,
            'items' => [
                ['description' => 'X', 'amount' => 100, 'cost_code_id' => $costCode->id],
            ],
        ])->assertStatus(422);
    }

    public function test_store_rejects_expense_payment_request(): void
    {
        $expensePr = PaymentRequest::factory()->expense()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'disbursed',
        ]);
        $costCode = CostCode::factory()->create();

        $this->postJson('/api/retirement-requests', [
            'payment_request_id' => $expensePr->id,
            'difference_type' => 'nil',
            'did_not_spend_money' => false,
            'items' => [
                ['description' => 'X', 'amount' => 100, 'cost_code_id' => $costCode->id],
            ],
        ])->assertStatus(422);
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_show_returns_detail(): void
    {
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $this->disbursedPr->id,
        ]);

        $this->getJson("/api/retirement-requests/{$retirement->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $retirement->id);
    }

    public function test_show_403_for_out_of_scope_branch(): void
    {
        $otherBranch = \App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
        $otherPr = PaymentRequest::factory()->advance()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $otherBranch->id,
            'currency_id' => $this->currency->id,
            'status' => 'disbursed',
        ]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $otherPr->id,
        ]);

        $this->getJson("/api/retirement-requests/{$retirement->id}")->assertForbidden();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_modifies_draft_retirement(): void
    {
        $costCode = CostCode::factory()->create();
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $this->disbursedPr->id,
            'status' => 'draft',
        ]);

        $this->putJson("/api/retirement-requests/{$retirement->id}", [
            'notes' => 'Updated retirement',
            'difference_type' => 'nil',
            'did_not_spend_money' => false,
            'items' => [
                ['description' => 'Updated item', 'amount' => 150.00, 'cost_code_id' => $costCode->id],
            ],
        ])->assertOk();
    }

    public function test_update_rejects_non_draft_non_sent_back(): void
    {
        $costCode = CostCode::factory()->create();
        $retirement = RetirementRequest::factory()->inWorkflow()->create([
            'payment_request_id' => $this->disbursedPr->id,
        ]);

        $this->putJson("/api/retirement-requests/{$retirement->id}", [
            'difference_type' => 'nil',
            'did_not_spend_money' => false,
            'items' => [
                ['description' => 'X', 'amount' => 50, 'cost_code_id' => $costCode->id],
            ],
        ])->assertStatus(422);
    }
}
