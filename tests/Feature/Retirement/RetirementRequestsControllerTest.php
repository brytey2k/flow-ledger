<?php

declare(strict_types=1);

namespace Tests\Feature\Retirement;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\AccountCode;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\RetirementService;
use Tests\TenantAppTestCase;

class RetirementRequestsControllerTest extends TenantAppTestCase
{
    private function disbursedAdvance(): PaymentRequest
    {
        return PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);
    }

    private function validItems(): array
    {
        $accountCode = AccountCode::factory()->create();

        return [
            ['description' => 'Hotel stay', 'amount' => '500.00', 'account_code_id' => $accountCode->id, 'receipt_number' => 'RCP-001'],
        ];
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('retirement-requests.index'))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_access_create(): void
    {
        $paymentRequest = $this->disbursedAdvance();
        $this->get(route('retirement-requests.create', $paymentRequest))->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessRetirementRequests->value);

        $this->actingAs($this->user)->get(route('retirement-requests.index'))->assertForbidden();
    }

    public function test_user_without_permission_cannot_create(): void
    {
        $this->role->revokePermissionTo(PermissionKey::CreateRetirementRequest->value);
        $paymentRequest = $this->disbursedAdvance();

        $this->actingAs($this->user)->get(route('retirement-requests.create', $paymentRequest))->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_renders(): void
    {
        $response = $this->actingAs($this->user)->get(route('retirement-requests.index'));

        $response->assertOk();
        $response->assertViewIs('tenant.retirement-requests.index');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_create_renders_for_disbursed_advance(): void
    {
        $paymentRequest = $this->disbursedAdvance();

        $response = $this->actingAs($this->user)->get(route('retirement-requests.create', $paymentRequest));

        $response->assertOk();
        $response->assertViewIs('tenant.retirement-requests.create');
    }

    public function test_create_rejects_non_disbursed_advance(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

        $this->actingAs($this->user)
            ->get(route('retirement-requests.create', $paymentRequest))
            ->assertStatus(422);
    }

    public function test_create_rejects_already_retired_advance(): void
    {
        $paymentRequest = $this->disbursedAdvance();
        RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id]);

        $this->actingAs($this->user)
            ->get(route('retirement-requests.create', $paymentRequest))
            ->assertStatus(422);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_draft_retirement(): void
    {
        $paymentRequest = $this->disbursedAdvance();
        $items = $this->validItems();

        $response = $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'notes' => 'Field trip expenses',
            'items' => $items,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('retirement_requests', [
            'payment_request_id' => $paymentRequest->id,
            'status' => 'draft',
        ]);
    }

    public function test_store_calculates_difference_correctly(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'total_amount' => 1000.00,
        ]);
        $accountCode = AccountCode::factory()->create();

        $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'items' => [
                ['description' => 'Item A', 'amount' => '600.00', 'account_code_id' => $accountCode->id, 'receipt_number' => null],
            ],
        ]);

        $this->assertDatabaseHas('retirement_requests', [
            'payment_request_id' => $paymentRequest->id,
            'total_amount_expended' => '600.00',
            'difference_amount' => '400.00',
            'difference_type' => 'refund_to_company',
        ]);
    }

    public function test_store_sets_pay_to_staff_when_overspent(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'total_amount' => 500.00,
        ]);
        $accountCode = AccountCode::factory()->create();

        $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'items' => [
                ['description' => 'Extra cost', 'amount' => '700.00', 'account_code_id' => $accountCode->id, 'receipt_number' => null],
            ],
        ]);

        $this->assertDatabaseHas('retirement_requests', [
            'payment_request_id' => $paymentRequest->id,
            'difference_type' => 'pay_to_staff',
        ]);
    }

    public function test_store_validation_requires_items(): void
    {
        $paymentRequest = $this->disbursedAdvance();

        $response = $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_store_validation_requires_account_code(): void
    {
        $paymentRequest = $this->disbursedAdvance();

        $response = $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'items' => [
                ['description' => 'Hotel', 'amount' => '100', 'account_code_id' => '', 'receipt_number' => null],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.account_code_id');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_show_renders(): void
    {
        $retirement = RetirementRequest::factory()->create();

        $response = $this->actingAs($this->user)->get(route('retirement-requests.show', $retirement));

        $response->assertOk();
        $response->assertViewIs('tenant.retirement-requests.show');
    }

    // ── Submit ────────────────────────────────────────────────────────────────

    public function test_submit_transitions_draft_to_in_workflow(): void
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

        $retirement = RetirementRequest::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'status' => 'in_workflow',
        ]);
    }

    public function test_submit_logs_activity(): void
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

        $retirement = RetirementRequest::factory()->create(['status' => 'draft']);

        $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => RetirementRequest::class,
            'subject_id' => $retirement->id,
            'event' => 'retirement.submitted',
        ]);
    }

    // ── Resubmit ──────────────────────────────────────────────────────────────

    public function test_resubmit_restores_in_workflow_after_send_back(): void
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

        $retirement = RetirementRequest::factory()->create(['status' => 'draft']);
        app(RetirementService::class)->submit($retirement);

        $retirement->refresh();
        $instance = $retirement->activeWorkflowInstance;
        $instanceStage = $instance->instanceStages()->first();
        $instanceStage->update(['status' => 'sent_back', 'completed_at' => now()]);
        $instance->update(['sent_back_to_stage_id' => $instanceStage->id]);
        $retirement->update(['status' => 'sent_back']);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.resubmit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $this->assertDatabaseHas('retirement_requests', ['id' => $retirement->id, 'status' => 'in_workflow']);
    }
}
