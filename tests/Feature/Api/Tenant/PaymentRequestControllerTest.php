<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use Tests\ApiTenantTestCase;

class PaymentRequestControllerTest extends ApiTenantTestCase
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

    public function test_index_returns_paginated_list(): void
    {
        PaymentRequest::factory()->count(3)->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->getJson('/api/payment-requests')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'last_page', 'per_page', 'total']]);
    }

    public function test_index_requires_permission(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessPaymentRequests->value);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');

        $this->getJson('/api/payment-requests')->assertForbidden();
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_draft_payment_request(): void
    {
        $response = $this->postJson('/api/payment-requests', [
            'currency_id' => $this->currency->id,
            'type' => 'advance',
            'notes' => 'Test advance',
            'items' => [
                ['description' => 'Item 1', 'amount' => 100.00, 'cost_code_id' => null],
            ],
        ])->assertCreated();

        $response->assertJsonPath('data.status', 'draft');
        $this->assertDatabaseHas('payment_requests', ['notes' => 'Test advance', 'status' => 'draft']);
    }

    public function test_store_requires_at_least_one_item(): void
    {
        $this->postJson('/api/payment-requests', [
            'currency_id' => $this->currency->id,
            'type' => 'advance',
            'items' => [],
        ])->assertUnprocessable()->assertJsonValidationErrors('items');
    }

    public function test_store_requires_create_permission(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreatePaymentRequest->value);
        $this->user->unsetRelation('roles')->unsetRelation('permissions');

        $this->postJson('/api/payment-requests', [
            'currency_id' => $this->currency->id,
            'type' => 'advance',
            'items' => [['description' => 'X', 'amount' => 10]],
        ])->assertForbidden();
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_show_returns_request_detail(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->getJson("/api/payment-requests/{$pr->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $pr->id);
    }

    public function test_show_403_for_out_of_scope_branch(): void
    {
        $otherBranch = \App\Models\Tenant\Branch::factory()->create(['level_id' => $this->level->id]);
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $otherBranch->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->getJson("/api/payment-requests/{$pr->id}")->assertForbidden();
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_modifies_draft_request(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'type' => 'advance',
        ]);

        $this->putJson("/api/payment-requests/{$pr->id}", [
            'currency_id' => $this->currency->id,
            'notes' => 'Updated notes',
            'items' => [['description' => 'Updated item', 'amount' => 200.00]],
        ])->assertOk()->assertJsonPath('data.notes', 'Updated notes');
    }

    public function test_update_rejects_non_draft_non_sent_back(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'approved',
        ]);

        $this->putJson("/api/payment-requests/{$pr->id}", [
            'currency_id' => $this->currency->id,
            'items' => [['description' => 'X', 'amount' => 10]],
        ])->assertStatus(422);
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_deletes_draft_request(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->deleteJson("/api/payment-requests/{$pr->id}")->assertNoContent();

        $this->assertSoftDeleted('payment_requests', ['id' => $pr->id]);
    }

    public function test_destroy_rejects_non_draft(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'in_workflow',
        ]);

        $this->deleteJson("/api/payment-requests/{$pr->id}")->assertStatus(422);
    }

}
