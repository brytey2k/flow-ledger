<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Tests\ApiTenantTestCase;

class PaymentRequestActionControllerTest extends ApiTenantTestCase
{
    private Staff $staff;
    private Currency $currency;

    protected function init(): void
    {
        parent::init();
        $this->staff = Staff::factory()->create(['user_id' => $this->user->id, 'branch_id' => $this->branch->id]);
        $this->currency = Currency::factory()->create();
    }

    // ── Submit ────────────────────────────────────────────────────────────────

    public function test_submit_transitions_draft_to_in_workflow(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'advance', 'branch_id' => null]);
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'type' => 'advance',
        ]);

        $this->postJson("/api/payment-requests/{$pr->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'in_workflow');
    }

    public function test_submit_fails_if_not_draft(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'in_workflow',
        ]);

        $this->postJson("/api/payment-requests/{$pr->id}/submit")->assertStatus(422);
    }

    public function test_submit_fails_if_no_workflow_template(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'type' => 'advance',
        ]);

        // No WorkflowTemplate created — should fail
        $this->postJson("/api/payment-requests/{$pr->id}/submit")
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'No workflow template is configured for this request type and branch.']);
    }

    public function test_submit_403_for_non_owner(): void
    {
        $otherStaff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $otherStaff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->postJson("/api/payment-requests/{$pr->id}/submit")->assertForbidden();
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function test_cancel_transitions_draft_to_cancelled(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->postJson("/api/payment-requests/{$pr->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cancel_fails_for_disbursed_request(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'disbursed',
        ]);

        $this->postJson("/api/payment-requests/{$pr->id}/cancel")->assertStatus(422);
    }

    // ── Resubmit ──────────────────────────────────────────────────────────────

    public function test_resubmit_fails_if_not_sent_back(): void
    {
        $pr = PaymentRequest::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
        ]);

        $this->postJson("/api/payment-requests/{$pr->id}/resubmit")->assertStatus(422);
    }
}
