<?php

declare(strict_types=1);

namespace Tests\Feature\Retirement;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use Tests\TenantAppTestCase;

class RetirementSettlementControllerTest extends TenantAppTestCase
{
    private function approvedRetirement(): RetirementRequest
    {
        $advance = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);

        return RetirementRequest::factory()->create([
            'payment_request_id' => $advance->id,
            'status' => 'approved',
            'difference_type' => 'refund_to_company',
            'difference_amount' => 50.00,
        ]);
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected(): void
    {
        $retirement = $this->approvedRetirement();

        $this->post(route('retirement-requests.settle', $retirement))
            ->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_settle(): void
    {
        $this->role->revokePermissionTo(PermissionKey::SettleRetirements->value);
        $retirement = $this->approvedRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement))
            ->assertForbidden();
    }

    // ── Happy path ───────────────────────────────────────────────────────────

    public function test_approved_retirement_can_be_settled(): void
    {
        $retirement = $this->approvedRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement), [
                'settlement_notes' => 'Cheque #1234 issued.',
            ])
            ->assertRedirect(route('retirement-requests.show', $retirement));

        $retirement->refresh();
        $this->assertSame('settled', $retirement->status);
        $this->assertNotNull($retirement->settled_at);
        $this->assertSame('Cheque #1234 issued.', $retirement->settlement_notes);
        $this->assertSame($this->user->id, $retirement->settled_by_user_id);
    }

    public function test_settlement_notes_are_optional(): void
    {
        $retirement = $this->approvedRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement))
            ->assertRedirect(route('retirement-requests.show', $retirement));

        $this->assertSame('settled', $retirement->fresh()->status);
    }

    public function test_nil_difference_retirement_can_also_be_settled(): void
    {
        $advance = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $advance->id,
            'status' => 'approved',
            'difference_type' => 'nil',
            'difference_amount' => 0,
        ]);

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement))
            ->assertRedirect(route('retirement-requests.show', $retirement));

        $this->assertSame('settled', $retirement->fresh()->status);
    }

    // ── Guard conditions ──────────────────────────────────────────────────────

    public function test_non_approved_retirement_cannot_be_settled(): void
    {
        $advance = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $advance->id,
            'status' => 'in_workflow',
        ]);

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement))
            ->assertRedirect();

        $this->assertSame('in_workflow', $retirement->fresh()->status);
    }

    // ── Activity log ─────────────────────────────────────────────────────────

    public function test_settlement_is_logged_in_activity(): void
    {
        $retirement = $this->approvedRetirement();

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement));

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => RetirementRequest::class,
            'subject_id' => $retirement->id,
            'event' => 'retirement.settled',
        ]);
    }

    public function test_settlement_fails_when_insufficient_cashbook_balance_for_pay_to_staff(): void
    {
        $advance = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'total_amount' => 100.00,
            'branch_id' => $this->branch->id,
        ]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $advance->id,
            'status' => 'approved',
            'difference_type' => 'pay_to_staff',
            'difference_amount' => 50.00,
        ]);

        // Pre-populate cashbook with insufficient balance for paying staff
        Cashbook::create([
            'branch_id' => $this->branch->id,
            'currency_id' => $advance->currency_id,
            'balance' => 30.00,
        ]);

        $this->actingAs($this->user)
            ->post(route('retirement-requests.settle', $retirement))
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame('approved', $retirement->fresh()->status);
    }
}
