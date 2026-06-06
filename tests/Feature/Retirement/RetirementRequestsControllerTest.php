<?php

declare(strict_types=1);

namespace Tests\Feature\Retirement;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\PaymentRequestItem;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\RetirementRequestItem;
use App\Models\Tenant\Staff;
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
            'branch_id' => $this->branch->id,
        ]);
    }

    private function validItems(): array
    {
        $costCode = CostCode::factory()->create();

        return [
            ['description' => 'Hotel stay', 'amount' => '500.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-001'],
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
        $response->assertSee(__('retirements.fields.no_spend_warning'), false);
    }

    public function test_create_rejects_expense_type(): void
    {
        $paymentRequest = PaymentRequest::factory()->expense()->create([
            'status' => 'disbursed',
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('retirement-requests.create', $paymentRequest))
            ->assertStatus(422);
    }

    public function test_create_rejects_non_disbursed_advance(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

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
            'did_not_spend_money' => '0',
            'items' => $items,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('retirement_requests', [
            'payment_request_id' => $paymentRequest->id,
            'status' => 'draft',
        ]);
    }

    public function test_store_rejects_expense_type(): void
    {
        $paymentRequest = PaymentRequest::factory()->expense()->create([
            'status' => 'disbursed',
            'branch_id' => $this->branch->id,
        ]);
        $items = $this->validItems();

        $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'notes' => 'Field trip expenses',
            'did_not_spend_money' => '0',
            'items' => $items,
        ])->assertStatus(422);

        $this->assertDatabaseMissing('retirement_requests', [
            'payment_request_id' => $paymentRequest->id,
        ]);
    }

    public function test_store_calculates_difference_correctly(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'total_amount' => 1000.00,
            'branch_id' => $this->branch->id,
        ]);
        $costCode = CostCode::factory()->create();

        $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'did_not_spend_money' => '0',
            'items' => [
                ['description' => 'Item A', 'amount' => '600.00', 'cost_code_id' => $costCode->id, 'receipt_number' => null],
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
            'branch_id' => $this->branch->id,
        ]);
        $costCode = CostCode::factory()->create();

        $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'did_not_spend_money' => '0',
            'items' => [
                ['description' => 'Extra cost', 'amount' => '700.00', 'cost_code_id' => $costCode->id, 'receipt_number' => null],
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
            'did_not_spend_money' => '0',
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_store_allows_zero_items_when_no_spend_is_checked(): void
    {
        $paymentRequest = $this->disbursedAdvance();

        $response = $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'did_not_spend_money' => '1',
            'items' => [],
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $retirement = RetirementRequest::query()->where('payment_request_id', $paymentRequest->id)->firstOrFail();
        $this->assertTrue($retirement->no_money_spent);
        $this->assertSame('0.00', $retirement->total_amount_expended);
        $this->assertSame('refund_to_company', $retirement->difference_type);
        $this->assertSame(0, $retirement->items()->count());
    }

    public function test_store_validation_requires_cost_code(): void
    {
        $paymentRequest = $this->disbursedAdvance();

        $response = $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'items' => [
                ['description' => 'Hotel', 'amount' => '100', 'cost_code_id' => '', 'receipt_number' => null],
            ],
        ]);

        $response->assertSessionHasErrors('items.0.cost_code_id');
    }

    // ── Show ──────────────────────────────────────────────────────────────────

    public function test_show_renders(): void
    {
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('retirement-requests.show', $retirement));

        $response->assertOk();
        $response->assertViewIs('tenant.retirement-requests.show');
    }

    // ── Show — with active workflow instance ──────────────────────────────────

    public function test_show_passes_active_instance_stage_when_workflow_is_in_progress(): void
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        $stage = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);
        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
        ]);
        app(RetirementService::class)->submit($retirement);

        $response = $this->actingAs($this->user)->get(route('retirement-requests.show', $retirement));

        $response->assertOk();
        $response->assertViewHas('canActOnActiveStage');
    }

    // ── Submit ────────────────────────────────────────────────────────────────

    public function test_submit_transitions_draft_to_in_workflow(): void
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
        ]);

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

        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
        ]);

        $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => RetirementRequest::class,
            'subject_id' => $retirement->id,
            'event' => 'retirement.submitted',
        ]);
    }

    public function test_cannot_submit_when_no_workflow_template_exists(): void
    {
        WorkflowTemplate::query()->delete();
        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('error');

        $retirement->refresh();
        $this->assertSame('draft', $retirement->status);
    }

    public function test_cannot_submit_when_workflow_template_has_no_stages(): void
    {
        WorkflowTemplate::factory()->retirement()->create();
        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('error');

        $retirement->refresh();
        $this->assertSame('draft', $retirement->status);
    }

    // ── Edit ─────────────────────────────────────────────────────────────────

    private function sentBackRetirementWithOwner(): RetirementRequest
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);

        return RetirementRequest::factory()->create([
            'status' => 'sent_back',
            'payment_request_id' => $paymentRequest->id,
        ]);
    }

    public function test_edit_renders_for_sent_back_retirement_owner(): void
    {
        $retirement = $this->sentBackRetirementWithOwner();

        $response = $this->actingAs($this->user)->get(route('retirement-requests.edit', $retirement));

        $response->assertOk();
        $response->assertViewIs('tenant.retirement-requests.edit');
        $response->assertViewHas(['retirementRequest', 'costCodes']);
        $response->assertSee(__('retirements.fields.no_spend_warning'), false);
    }

    public function test_edit_renders_for_draft_retirement_owner(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);
        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => $paymentRequest->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('retirement-requests.edit', $retirement));

        $response->assertOk();
        $response->assertViewIs('tenant.retirement-requests.edit');
    }

    public function test_edit_redirects_if_not_editable_status(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);
        $retirement = RetirementRequest::factory()->create([
            'status' => 'in_workflow',
            'payment_request_id' => $paymentRequest->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('retirement-requests.edit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('error', __('flash.retirements.edit_only_sent_back'));
    }

    public function test_edit_redirects_if_not_owner(): void
    {
        $retirement = RetirementRequest::factory()->create([
            'status' => 'sent_back',
            'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
        ]);

        $response = $this->actingAs($this->user)->get(route('retirement-requests.edit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('error', __('flash.retirements.edit_not_owner'));
    }

    // ── Update ────────────────────────────────────────────────────────────────

    public function test_update_saves_changes_and_redirects_to_show(): void
    {
        $retirement = $this->sentBackRetirementWithOwner();
        $costCode = CostCode::factory()->create();

        $response = $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
            'notes' => 'Updated retirement notes',
            'did_not_spend_money' => '0',
            'items' => [
                ['description' => 'Updated hotel', 'amount' => '600.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-002'],
            ],
        ]);

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'notes' => 'Updated retirement notes',
            'total_amount_expended' => '600.00',
            'status' => 'sent_back',
        ]);
        $this->assertDatabaseHas('retirement_request_items', [
            'retirement_request_id' => $retirement->id,
            'description' => 'Updated hotel',
        ]);
    }

    public function test_update_recalculates_difference_type(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'staff_id' => $staff->id,
            'total_amount' => 1000.00,
            'branch_id' => $this->branch->id,
        ]);
        $retirement = RetirementRequest::factory()->create([
            'status' => 'sent_back',
            'payment_request_id' => $paymentRequest->id,
            'difference_type' => 'nil',
        ]);
        $costCode = CostCode::factory()->create();

        $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
            'did_not_spend_money' => '0',
            'items' => [
                ['description' => 'Extra expense', 'amount' => '1200.00', 'cost_code_id' => $costCode->id, 'receipt_number' => null],
            ],
        ]);

        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'total_amount_expended' => '1200.00',
            'difference_amount' => '200.00',
            'difference_type' => 'pay_to_staff',
        ]);
    }

    public function test_update_saves_draft_changes(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);
        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => $paymentRequest->id,
        ]);
        $costCode = CostCode::factory()->create();

        $response = $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
            'notes' => 'Draft edit notes',
            'did_not_spend_money' => '0',
            'items' => [['description' => 'Fuel', 'amount' => '150.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-D01']],
        ]);

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'notes' => 'Draft edit notes',
            'status' => 'draft',
        ]);
    }

    public function test_update_rejects_non_editable_status(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);
        $retirement = RetirementRequest::factory()->create([
            'status' => 'in_workflow',
            'payment_request_id' => $paymentRequest->id,
        ]);
        $costCode = CostCode::factory()->create();

        $response = $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
            'did_not_spend_money' => '0',
            'items' => [['description' => 'Item', 'amount' => '100', 'cost_code_id' => $costCode->id]],
        ]);

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('error', __('flash.retirements.edit_only_sent_back'));
    }

    public function test_update_rejects_non_owner(): void
    {
        $retirement = RetirementRequest::factory()->create([
            'status' => 'sent_back',
            'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
        ]);
        $costCode = CostCode::factory()->create();

        $response = $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
            'did_not_spend_money' => '0',
            'items' => [['description' => 'Item', 'amount' => '100', 'cost_code_id' => $costCode->id]],
        ]);

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('error', __('flash.retirements.edit_not_owner'));
    }

    public function test_update_validation_requires_items(): void
    {
        $retirement = $this->sentBackRetirementWithOwner();

        $response = $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
            'did_not_spend_money' => '0',
            'items' => [],
        ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_update_allows_zero_items_when_no_spend_is_checked(): void
    {
        $retirement = $this->sentBackRetirementWithOwner();

        $response = $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
            'did_not_spend_money' => '1',
            'items' => [],
        ]);

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('success');

        $retirement->refresh();
        $this->assertTrue($retirement->no_money_spent);
        $this->assertSame('0.00', $retirement->total_amount_expended);
        $this->assertSame('refund_to_company', $retirement->difference_type);
        $this->assertSame(0, $retirement->items()->count());
    }

    // ── Resubmit ──────────────────────────────────────────────────────────────

    public function test_resubmit_restores_in_workflow_after_send_back(): void
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'staff_id' => $staff->id,
            'branch_id' => $this->branch->id,
        ]);
        $retirement = RetirementRequest::factory()->create([
            'status' => 'draft',
            'payment_request_id' => $paymentRequest->id,
        ]);
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

    public function test_non_owner_cannot_resubmit_retirement(): void
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

        $retirement = RetirementRequest::factory()->create([
            'status' => 'sent_back',
            'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('retirement-requests.resubmit', $retirement));

        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('retirement_requests', ['id' => $retirement->id, 'status' => 'sent_back']);
    }

    public function test_cancel_allows_recreate(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
        ]);
        $items = $this->validItems();

        // Create initial retirement
        $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'notes' => 'Initial retirement',
            'did_not_spend_money' => '0',
            'items' => $items,
        ]);

        $retirement = RetirementRequest::query()->where('payment_request_id', $paymentRequest->id)->firstOrFail();

        // Cancel it
        $response = $this->actingAs($this->user)->post(route('retirement-requests.cancel', $retirement));
        $response->assertRedirect(route('retirement-requests.show', $retirement));
        $response->assertSessionHas('success');

        $retirement->refresh();
        $this->assertSame('cancelled', $retirement->status);

        // Attempt to create a new retirement after cancellation
        $response = $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'notes' => 'Recreated retirement',
            'did_not_spend_money' => '0',
            'items' => $items,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('retirement_requests', [
            'payment_request_id' => $paymentRequest->id,
            'status' => 'draft',
        ]);
    }

    // ── Receipt number uniqueness ─────────────────────────────────────────────

    public function test_store_rejects_duplicate_receipt_number_already_in_retirement_items(): void
    {
        RetirementRequestItem::factory()->create(['receipt_number' => 'RCP-DUPE']);
        $paymentRequest = $this->disbursedAdvance();
        $costCode = CostCode::factory()->create();

        $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'did_not_spend_money' => '0',
            'items' => [['description' => 'Hotel', 'amount' => '200.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-DUPE']],
        ])->assertSessionHasErrors(['items.0.receipt_number']);
    }

    public function test_store_rejects_receipt_number_already_used_in_payment_request_items(): void
    {
        PaymentRequestItem::factory()->create(['receipt_number' => 'RCP-CROSS']);
        $paymentRequest = $this->disbursedAdvance();
        $costCode = CostCode::factory()->create();

        $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'did_not_spend_money' => '0',
            'items' => [['description' => 'Fuel', 'amount' => '100.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-CROSS']],
        ])->assertSessionHasErrors(['items.0.receipt_number']);
    }

    public function test_store_rejects_duplicate_receipt_numbers_within_same_submission(): void
    {
        $paymentRequest = $this->disbursedAdvance();
        $costCode = CostCode::factory()->create();

        $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
            'did_not_spend_money' => '0',
            'items' => [
                ['description' => 'Item A', 'amount' => '100.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-SAME'],
                ['description' => 'Item B', 'amount' => '200.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-SAME'],
            ],
        ])->assertSessionHasErrors(['items.0.receipt_number', 'items.1.receipt_number']);
    }

    public function test_update_allows_same_receipt_numbers_for_existing_request(): void
    {
        $retirement = $this->sentBackRetirementWithOwner();
        $costCode = CostCode::factory()->create();
        RetirementRequestItem::factory()->create([
            'retirement_request_id' => $retirement->id,
            'receipt_number' => 'RCP-KEEP',
            'cost_code_id' => $costCode->id,
        ]);

        $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
            'did_not_spend_money' => '0',
            'items' => [['description' => 'Hotel', 'amount' => '500.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-KEEP']],
        ])->assertSessionHasNoErrors()->assertRedirect();
    }
}
