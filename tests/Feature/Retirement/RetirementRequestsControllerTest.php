<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
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

function disbursedAdvanceForRetirementRequestsController(): PaymentRequest
{
    return PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => test()->branch->id,
    ]);
}
function validItems(): array
{
    $costCode = CostCode::factory()->create();

    return [
        ['description' => 'Hotel stay', 'amount' => '500.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-001'],
    ];
}
test('guest is redirected from index', function () {
    $this->get(route('retirement-requests.index'))->assertRedirect(route('login'));
});
test('guest cannot access create', function () {
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();
    $this->get(route('retirement-requests.create', $paymentRequest))->assertRedirect(route('login'));
});
test('user without permission cannot access index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessRetirementRequests->value);

    $this->actingAs($this->user)->get(route('retirement-requests.index'))->assertForbidden();
});
test('user without permission cannot create', function () {
    $this->role->revokePermissionTo(PermissionKey::CreateRetirementRequest->value);
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();

    $this->actingAs($this->user)->get(route('retirement-requests.create', $paymentRequest))->assertForbidden();
});
test('index renders', function () {
    $response = $this->actingAs($this->user)->get(route('retirement-requests.index'));

    $response->assertOk();
    $response->assertViewIs('tenant.retirement-requests.index');
});
test('create renders for disbursed advance', function () {
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();

    $response = $this->actingAs($this->user)->get(route('retirement-requests.create', $paymentRequest));

    $response->assertOk();
    $response->assertViewIs('tenant.retirement-requests.create');
    $response->assertSee(__('retirements.fields.no_spend_warning'), false);
});
test('create rejects expense type', function () {
    $paymentRequest = PaymentRequest::factory()->expense()->create([
        'status' => 'disbursed',
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('retirement-requests.create', $paymentRequest))
        ->assertStatus(422);
});
test('create rejects non disbursed advance', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved', 'branch_id' => $this->branch->id]);

    $this->actingAs($this->user)
        ->get(route('retirement-requests.create', $paymentRequest))
        ->assertStatus(422);
});
test('create rejects advance with pending retirement', function () {
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();
    RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id, 'status' => 'in_workflow']);

    $this->actingAs($this->user)
        ->get(route('retirement-requests.create', $paymentRequest))
        ->assertStatus(422);
});
test('create rejects already retired advance', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'retired',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('retirement-requests.create', $paymentRequest))
        ->assertStatus(422);
});
test('store creates draft retirement', function () {
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();
    $items = validItems();

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
});
test('store rejects expense type', function () {
    $paymentRequest = PaymentRequest::factory()->expense()->create([
        'status' => 'disbursed',
        'branch_id' => $this->branch->id,
    ]);
    $items = validItems();

    $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
        'notes' => 'Field trip expenses',
        'did_not_spend_money' => '0',
        'items' => $items,
    ])->assertStatus(422);

    $this->assertDatabaseMissing('retirement_requests', [
        'payment_request_id' => $paymentRequest->id,
    ]);
});
test('store calculates difference correctly', function () {
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
});
test('store sets pay to staff when overspent', function () {
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
});
test('store validation requires items', function () {
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();

    $response = $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
        'did_not_spend_money' => '0',
        'items' => [],
    ]);

    $response->assertSessionHasErrors('items');
});
test('store allows zero items when no spend is checked', function () {
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();

    $response = $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
        'did_not_spend_money' => '1',
        'items' => [],
    ]);

    $response->assertRedirect();
    $response->assertSessionHas('success');

    $retirement = RetirementRequest::query()->where('payment_request_id', $paymentRequest->id)->firstOrFail();
    expect($retirement->no_money_spent)->toBeTrue();
    expect($retirement->total_amount_expended)->toBe('0.00');
    expect($retirement->difference_type)->toBe('refund_to_company');
    expect($retirement->items()->count())->toBe(0);
});
test('store validation requires cost code', function () {
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();

    $response = $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
        'items' => [
            ['description' => 'Hotel', 'amount' => '100', 'cost_code_id' => '', 'receipt_number' => null],
        ],
    ]);

    $response->assertSessionHasErrors('items.0.cost_code_id');
});
test('show renders', function () {
    $retirement = RetirementRequest::factory()->create([
        'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('retirement-requests.show', $retirement));

    $response->assertOk();
    $response->assertViewIs('tenant.retirement-requests.show');
});
test('show passes active instance stage when workflow is in progress', function () {
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
});
// ── Submit ────────────────────────────────────────────────────────────────
function draftRetirementWithOwner(): RetirementRequest
{
    $staff = Staff::factory()->withUser(test()->user)->withBranch(test()->branch)->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'staff_id' => $staff->id,
        'branch_id' => test()->branch->id,
    ]);

    return RetirementRequest::factory()->create([
        'status' => 'draft',
        'payment_request_id' => $paymentRequest->id,
    ]);
}
test('submit transitions draft to in workflow', function () {
    $template = WorkflowTemplate::factory()->retirement()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

    $retirement = draftRetirementWithOwner();

    $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

    $response->assertRedirect(route('retirement-requests.show', $retirement));
    $this->assertDatabaseHas('retirement_requests', [
        'id' => $retirement->id,
        'status' => 'in_workflow',
    ]);
});
test('submit logs activity', function () {
    $template = WorkflowTemplate::factory()->retirement()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

    $retirement = draftRetirementWithOwner();

    $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

    $this->assertDatabaseHas('activity_log', [
        'subject_type' => RetirementRequest::class,
        'subject_id' => $retirement->id,
        'event' => 'retirement.submitted',
    ]);
});
test('cannot submit when no workflow template exists', function () {
    WorkflowTemplate::query()->delete();
    $retirement = draftRetirementWithOwner();

    $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

    $response->assertRedirect(route('retirement-requests.show', $retirement));
    $response->assertSessionHas('error');

    $retirement->refresh();
    expect($retirement->status)->toBe('draft');
});
test('cannot submit when workflow template has no stages', function () {
    WorkflowTemplate::factory()->retirement()->create();
    $retirement = draftRetirementWithOwner();

    $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

    $response->assertRedirect(route('retirement-requests.show', $retirement));
    $response->assertSessionHas('error');

    $retirement->refresh();
    expect($retirement->status)->toBe('draft');
});
test('non owner cannot submit retirement', function () {
    $template = WorkflowTemplate::factory()->retirement()->create();
    WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);

    $retirement = RetirementRequest::factory()->create([
        'status' => 'draft',
        'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
    ]);

    $response = $this->actingAs($this->user)->post(route('retirement-requests.submit', $retirement));

    $response->assertRedirect(route('retirement-requests.show', $retirement));
    $response->assertSessionHas('error');

    $this->assertDatabaseHas('retirement_requests', ['id' => $retirement->id, 'status' => 'draft']);
});
// ── Edit ─────────────────────────────────────────────────────────────────
function sentBackRetirementWithOwner(): RetirementRequest
{
    $staff = Staff::factory()->withUser(test()->user)->withBranch(test()->branch)->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'staff_id' => $staff->id,
        'branch_id' => test()->branch->id,
    ]);

    return RetirementRequest::factory()->create([
        'status' => 'sent_back',
        'payment_request_id' => $paymentRequest->id,
    ]);
}
test('edit renders for sent back retirement owner', function () {
    $retirement = sentBackRetirementWithOwner();

    $response = $this->actingAs($this->user)->get(route('retirement-requests.edit', $retirement));

    $response->assertOk();
    $response->assertViewIs('tenant.retirement-requests.edit');
    $response->assertViewHas(['retirementRequest', 'costCodes']);
    $response->assertSee(__('retirements.fields.no_spend_warning'), false);
});
test('edit renders for draft retirement owner', function () {
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
});
test('edit redirects if not editable status', function () {
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
});
test('edit redirects if not owner', function () {
    $retirement = RetirementRequest::factory()->create([
        'status' => 'sent_back',
        'payment_request_id' => PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now(), 'branch_id' => $this->branch->id])->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('retirement-requests.edit', $retirement));

    $response->assertRedirect(route('retirement-requests.show', $retirement));
    $response->assertSessionHas('error', __('flash.retirements.edit_not_owner'));
});
test('update saves changes and redirects to show', function () {
    $retirement = sentBackRetirementWithOwner();
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
});
test('update recalculates difference type', function () {
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
});
test('update saves draft changes', function () {
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
});
test('update rejects non editable status', function () {
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
});
test('update rejects non owner', function () {
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
});
test('update validation requires items', function () {
    $retirement = sentBackRetirementWithOwner();

    $response = $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
        'did_not_spend_money' => '0',
        'items' => [],
    ]);

    $response->assertSessionHasErrors('items');
});
test('update allows zero items when no spend is checked', function () {
    $retirement = sentBackRetirementWithOwner();

    $response = $this->actingAs($this->user)->put(route('retirement-requests.update', $retirement), [
        'did_not_spend_money' => '1',
        'items' => [],
    ]);

    $response->assertRedirect(route('retirement-requests.show', $retirement));
    $response->assertSessionHas('success');

    $retirement->refresh();
    expect($retirement->no_money_spent)->toBeTrue();
    expect($retirement->total_amount_expended)->toBe('0.00');
    expect($retirement->difference_type)->toBe('refund_to_company');
    expect($retirement->items()->count())->toBe(0);
});
test('resubmit restores in workflow after send back', function () {
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
});
test('non owner cannot resubmit retirement', function () {
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
});
test('cancel allows recreate', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
        'staff_id' => $staff->id,
    ]);
    $items = validItems();

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
    expect($retirement->status)->toBe('cancelled');

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
});
test('store rejects duplicate receipt number already in retirement items', function () {
    RetirementRequestItem::factory()->create(['receipt_number' => 'RCP-DUPE']);
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();
    $costCode = CostCode::factory()->create();

    $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
        'did_not_spend_money' => '0',
        'items' => [['description' => 'Hotel', 'amount' => '200.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-DUPE']],
    ])->assertSessionHasErrors(['items.0.receipt_number']);
});
test('store rejects receipt number already used in payment request items', function () {
    PaymentRequestItem::factory()->create(['receipt_number' => 'RCP-CROSS']);
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();
    $costCode = CostCode::factory()->create();

    $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
        'did_not_spend_money' => '0',
        'items' => [['description' => 'Fuel', 'amount' => '100.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-CROSS']],
    ])->assertSessionHasErrors(['items.0.receipt_number']);
});
test('store rejects duplicate receipt numbers within same submission', function () {
    $paymentRequest = disbursedAdvanceForRetirementRequestsController();
    $costCode = CostCode::factory()->create();

    $this->actingAs($this->user)->post(route('retirement-requests.store', $paymentRequest), [
        'did_not_spend_money' => '0',
        'items' => [
            ['description' => 'Item A', 'amount' => '100.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-SAME'],
            ['description' => 'Item B', 'amount' => '200.00', 'cost_code_id' => $costCode->id, 'receipt_number' => 'RCP-SAME'],
        ],
    ])->assertSessionHasErrors(['items.0.receipt_number', 'items.1.receipt_number']);
});
test('update allows same receipt numbers for existing request', function () {
    $retirement = sentBackRetirementWithOwner();
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
});
