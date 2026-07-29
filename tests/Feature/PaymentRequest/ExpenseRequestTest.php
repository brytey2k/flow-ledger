<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\CostCode;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;

function linkUserToStaffForExpenseRequest(): Staff
{
    return Staff::factory()->withUser(test()->user)->withBranch(test()->branch)->create();
}
function validExpensePayload(array $override = []): array
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
test('create form provides cost codes', function () {
    linkUserToStaffForExpenseRequest();
    CostCode::factory()->count(3)->create();

    $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

    $response->assertOk();
    $response->assertViewHas('costCodes');
});
test('expense request is created as draft', function () {
    linkUserToStaffForExpenseRequest();
    $payload = validExpensePayload();

    $response = $this->actingAs($this->user)->post(route('payment-requests.store'), $payload);

    $response->assertRedirect();
    $this->assertDatabaseHas('payment_requests', [
        'type' => 'expense',
        'status' => 'draft',
    ]);
});
test('expense items store cost code and receipt', function () {
    linkUserToStaffForExpenseRequest();
    $costCode = CostCode::factory()->create();
    $payload = validExpensePayload([
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
});
test('expense requires cost code on items', function () {
    linkUserToStaffForExpenseRequest();
    $payload = validExpensePayload([
        'items' => [[
            'description' => 'Taxi',
            'amount' => '50.00',
            'cost_code_id' => '',
            'receipt_number' => null,
        ]],
    ]);

    $response = $this->actingAs($this->user)->post(route('payment-requests.store'), $payload);

    $response->assertSessionHasErrors('items.0.cost_code_id');
});
test('advance does not require cost code', function () {
    linkUserToStaffForExpenseRequest();
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
});
test('expense goes through workflow and becomes approved', function () {
    $template = WorkflowTemplate::factory()->expense()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

    $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'draft']);

    app(PaymentRequestService::class)->submit($paymentRequest, $this->user);

    $this->assertDatabaseHas('payment_requests', [
        'id' => $paymentRequest->id,
        'status' => 'in_workflow',
    ]);

    $this->assertDatabaseHas('workflow_instances', [
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
    ]);
});
test('show renders expense request', function () {
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
    $response->assertSee('colspan="3"', false);
});
test('show does not offer retirement action for disbursed expense', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $paymentRequest = PaymentRequest::factory()->expense()->create([
        'status' => 'disbursed',
        'branch_id' => $this->branch->id,
        'staff_id' => $staff->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.show', $paymentRequest));

    $response->assertOk();
    $response->assertDontSee(route('retirement-requests.create', $paymentRequest), false);
    $response->assertDontSee(__('payment_requests.buttons.retire'));
});
