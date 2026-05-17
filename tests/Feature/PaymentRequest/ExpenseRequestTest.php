<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use Tests\TenantAppTestCase;

class ExpenseRequestTest extends TenantAppTestCase
{
    private function linkUserToStaff(): Staff
    {
        return Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    }

    private function validExpensePayload(array $override = []): array
    {
        $costCode = CostCode::factory()->create();

        return array_merge([
            'currency_id' => Currency::factory()->create()->id,
            'type' => 'expense',
            'notes' => null,
            'items' => [
                [
                    'description' => 'Flight ticket',
                    'amount' => '350.00',
                    'cost_code_id' => $costCode->id,
                    'receipt_number' => 'RCP-100',
                ],
            ],
        ], $override);
    }

    // ── Create form ───────────────────────────────────────────────────────────

    public function test_create_form_provides_cost_codes(): void
    {
        $this->linkUserToStaff();
        CostCode::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

        $response->assertOk();
        $response->assertViewHas('costCodes');
    }

    // ── Store: expense ────────────────────────────────────────────────────────

    public function test_expense_request_is_created_as_draft(): void
    {
        $this->linkUserToStaff();
        $payload = $this->validExpensePayload();

        $response = $this->actingAs($this->user)->post(route('payment-requests.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('payment_requests', [
            'type' => 'expense',
            'status' => 'draft',
        ]);
    }

    public function test_expense_items_store_cost_code_and_receipt(): void
    {
        $this->linkUserToStaff();
        $costCode = CostCode::factory()->create();
        $payload = $this->validExpensePayload([
            'items' => [[
                'description' => 'Hotel stay',
                'amount' => '200.00',
                'cost_code_id' => $costCode->id,
                'receipt_number' => 'HTL-001',
            ]],
        ]);

        $this->actingAs($this->user)->post(route('payment-requests.store'), $payload);

        $this->assertDatabaseHas('payment_request_items', [
            'description' => 'Hotel stay',
            'cost_code_id' => $costCode->id,
            'receipt_number' => 'HTL-001',
        ]);
    }

    public function test_expense_requires_cost_code_on_items(): void
    {
        $this->linkUserToStaff();
        $payload = $this->validExpensePayload([
            'items' => [[
                'description' => 'Taxi',
                'amount' => '50.00',
                'cost_code_id' => '',
                'receipt_number' => null,
            ]],
        ]);

        $response = $this->actingAs($this->user)->post(route('payment-requests.store'), $payload);

        $response->assertSessionHasErrors('items.0.cost_code_id');
    }

    public function test_advance_does_not_require_cost_code(): void
    {
        $this->linkUserToStaff();
        $payload = [
            'currency_id' => Currency::factory()->create()->id,
            'type' => 'advance',
            'notes' => null,
            'items' => [
                ['description' => 'Planned transport', 'amount' => '100.00', 'cost_code_id' => '', 'receipt_number' => null],
            ],
        ];

        $response = $this->actingAs($this->user)->post(route('payment-requests.store'), $payload);

        $response->assertSessionMissing('errors');
        $this->assertDatabaseHas('payment_requests', ['type' => 'advance', 'status' => 'draft']);
    }

    // ── Expense workflow ──────────────────────────────────────────────────────

    public function test_expense_uses_expense_workflow_template(): void
    {
        $template = WorkflowTemplate::factory()->expense()->create();
        WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

        $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

        app(PaymentRequestService::class)->submit($paymentRequest, $this->user);

        $this->assertDatabaseHas('workflow_instances', [
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
        ]);
    }

    // ── Show: expense items columns ───────────────────────────────────────────

    public function test_show_renders_expense_request(): void
    {
        $costCode = CostCode::factory()->create();
        $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'draft', 'branch_id' => $this->branch->id]);
        $paymentRequest->items()->create([
            'description' => 'Flight',
            'amount' => 300,
            'cost_code_id' => $costCode->id,
            'receipt_number' => 'FL-001',
        ]);

        $response = $this->actingAs($this->user)->get(route('payment-requests.show', $paymentRequest));

        $response->assertOk();
        $response->assertSee('FL-001');
        $response->assertSee($costCode->code);
    }
}
