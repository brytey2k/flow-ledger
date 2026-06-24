<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Tests\ApiTenantTestCase;

class RetirementRequestActionControllerTest extends ApiTenantTestCase
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

    private function makeDraftRetirement(PaymentRequest|null $pr = null): RetirementRequest
    {
        return RetirementRequest::factory()->create([
            'payment_request_id' => ($pr ?? $this->disbursedPr)->id,
            'status' => 'draft',
        ]);
    }

    private function createRetirementTemplate(): void
    {
        $template = WorkflowTemplate::factory()->create(['type' => 'retirement', 'branch_id' => null]);
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        // Use a separate role so the submitting user is not auto-approved as the approver
        $approverRole = \App\Models\Role::create(['name' => 'ret_approver_' . uniqid(), 'guard_name' => 'web']);
        $stage->roles()->attach($approverRole->id);
    }

    // ── Submit ────────────────────────────────────────────────────────────────

    public function test_submit_transitions_draft_to_in_workflow(): void
    {
        $this->createRetirementTemplate();
        $retirement = $this->makeDraftRetirement();

        $this->postJson("/api/retirement-requests/{$retirement->id}/submit")
            ->assertOk()
            ->assertJsonPath('data.status', 'in_workflow');
    }

    public function test_submit_fails_if_not_draft(): void
    {
        $retirement = RetirementRequest::factory()->inWorkflow()->create([
            'payment_request_id' => $this->disbursedPr->id,
        ]);

        $this->postJson("/api/retirement-requests/{$retirement->id}/submit")
            ->assertStatus(422);
    }

    public function test_submit_403_for_non_owner(): void
    {
        $otherStaff = Staff::factory()->create(['branch_id' => $this->branch->id]);
        $otherPr = PaymentRequest::factory()->advance()->create([
            'staff_id' => $otherStaff->id,
            'branch_id' => $this->branch->id,
            'currency_id' => $this->currency->id,
            'status' => 'disbursed',
        ]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $otherPr->id,
            'status' => 'draft',
        ]);

        $this->postJson("/api/retirement-requests/{$retirement->id}/submit")
            ->assertForbidden();
    }

    // ── Cancel ────────────────────────────────────────────────────────────────

    public function test_cancel_transitions_draft_to_cancelled(): void
    {
        $retirement = $this->makeDraftRetirement();

        $this->postJson("/api/retirement-requests/{$retirement->id}/cancel")
            ->assertOk()
            ->assertJsonPath('data.status', 'cancelled');
    }

    public function test_cancel_fails_for_non_cancellable_status(): void
    {
        $retirement = RetirementRequest::factory()->approved()->create([
            'payment_request_id' => $this->disbursedPr->id,
        ]);

        $this->postJson("/api/retirement-requests/{$retirement->id}/cancel")
            ->assertStatus(422);
    }

    // ── Resubmit ──────────────────────────────────────────────────────────────

    public function test_resubmit_fails_if_not_sent_back(): void
    {
        $retirement = $this->makeDraftRetirement();

        $this->postJson("/api/retirement-requests/{$retirement->id}/resubmit")
            ->assertStatus(422);
    }
}
