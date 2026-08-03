<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\Branch;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\PaymentRequestItem;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\RetirementRequestItem;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;

test('guest is redirected from index', function () {
    $response = $this->get(route('payment-requests.index'));

    $response->assertRedirect(route('login'));
});
test('guest is redirected from create', function () {
    $response = $this->get(route('payment-requests.create'));

    $response->assertRedirect(route('login'));
});
test('user without permission cannot access index', function () {
    $this->role->revokePermissionTo('access payment requests');

    $response = $this->actingAs($this->user)->get(route('payment-requests.index'));

    $response->assertForbidden();
});
test('user without permission cannot access create form', function () {
    $this->role->revokePermissionTo('create payment request');

    $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

    $response->assertForbidden();
});
test('user without permission cannot access show', function () {
    $request = PaymentRequest::factory()->create(['branch_id' => $this->branch->id]);
    $this->role->revokePermissionTo('access payment requests');

    $response = $this->actingAs($this->user)->get(route('payment-requests.show', $request));

    $response->assertForbidden();
});
test('user without delete permission cannot delete', function () {
    $request = PaymentRequest::factory()->create(['branch_id' => $this->branch->id]);
    $this->role->revokePermissionTo('delete payment request');

    $response = $this->actingAs($this->user)->delete(route('payment-requests.destroy', $request));

    $response->assertForbidden();
});
test('index renders with requests', function () {
    PaymentRequest::factory()->count(3)->create(['branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.index'));

    $response->assertOk();
    $response->assertViewIs('tenant.payment-requests.index');
    $response->assertViewHas('requests');
});
test('index shows empty state when no requests', function () {
    PaymentRequest::query()->delete();

    $response = $this->actingAs($this->user)->get(route('payment-requests.index'));

    $response->assertOk();
    $response->assertSee('No requests yet');
});
// ── Index Filters ─────────────────────────────────────────────────────────
function disbursedAdvanceForPaymentRequestController(int|null $staffId = null): PaymentRequest
{
    return PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => test()->branch->id,
        'staff_id' => $staffId ?? Staff::factory()->withBranch(test()->branch)->create()->id,
    ]);
}
test('index pending retirement filter lists disbursed advances without retirement', function () {
    $advance = disbursedAdvanceForPaymentRequestController();
    PaymentRequest::factory()->create(['branch_id' => $this->branch->id, 'status' => 'draft']);

    $response = $this->actingAs($this->user)->get(route('payment-requests.index', ['status' => 'pending_retirement']));

    $response->assertOk();
    $response->assertViewHas('requests', fn($requests) => $requests->contains('id', $advance->id) && $requests->count() === 1);
});
test('index pending retirement filter excludes advances with active retirement', function () {
    $withActiveRetirement = disbursedAdvanceForPaymentRequestController();
    RetirementRequest::factory()->create(['payment_request_id' => $withActiveRetirement->id, 'status' => 'in_workflow']);

    $withCancelledOnly = disbursedAdvanceForPaymentRequestController();
    RetirementRequest::factory()->create(['payment_request_id' => $withCancelledOnly->id, 'status' => 'cancelled']);

    $response = $this->actingAs($this->user)->get(route('payment-requests.index', ['status' => 'pending_retirement']));

    $response->assertOk();
    $response->assertViewHas('requests', fn($requests) => ! $requests->contains('id', $withActiveRetirement->id)
            && $requests->contains('id', $withCancelledOnly->id));
});
test('index scope mine only shows current users requests', function () {
    $myStaff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $mine = PaymentRequest::factory()->create(['branch_id' => $this->branch->id, 'staff_id' => $myStaff->id]);
    $others = PaymentRequest::factory()->create(['branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.index', ['scope' => 'mine']));

    $response->assertOk();
    $response->assertViewHas('requests', fn($requests) => $requests->contains('id', $mine->id) && ! $requests->contains('id', $others->id));
});
test('index scope branch is default and unchanged', function () {
    $myStaff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $mine = PaymentRequest::factory()->create(['branch_id' => $this->branch->id, 'staff_id' => $myStaff->id]);
    $others = PaymentRequest::factory()->create(['branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.index'));

    $response->assertOk();
    $response->assertViewHas('requests', fn($requests) => $requests->contains('id', $mine->id) && $requests->contains('id', $others->id));
});
test('index status filter narrows to specific status', function () {
    $approved = PaymentRequest::factory()->create(['branch_id' => $this->branch->id, 'status' => 'approved']);
    PaymentRequest::factory()->create(['branch_id' => $this->branch->id, 'status' => 'draft']);

    $response = $this->actingAs($this->user)->get(route('payment-requests.index', ['status' => 'approved']));

    $response->assertOk();
    $response->assertViewHas('requests', fn($requests) => $requests->contains('id', $approved->id) && $requests->count() === 1);
});
test('retire button shown only for owner of eligible advance', function () {
    $myStaff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $mine = disbursedAdvanceForPaymentRequestController($myStaff->id);
    $others = disbursedAdvanceForPaymentRequestController();

    $response = $this->actingAs($this->user)->get(route('payment-requests.index'));

    $response->assertOk();
    $response->assertSee(route('retirement-requests.create', $mine), false);
    $response->assertDontSee(route('retirement-requests.create', $others), false);
});
test('create form renders', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

    $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

    $response->assertOk();
    $response->assertViewIs('tenant.payment-requests.create');
    $response->assertViewHas(['staffProfile', 'costCodes']);
});
test('create form redirects if no staff profile', function () {
    $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

    $response->assertRedirect(route('payment-requests.index'));
    $response->assertSessionHas('error');
    $response->assertSessionHas('error', __('flash.requests.missing_staff_profile'));
});
test('create form redirects if staff has no branch', function () {
    Staff::factory()->withUser($this->user)->create();

    // no branch
    $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

    $response->assertRedirect(route('payment-requests.index'));
    $response->assertSessionHas('error');
});
test('store creates draft and redirects to show', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'advance',
        'currency_id' => $currency->id,
        'notes' => 'Test notes',
        'items' => [
            ['description' => 'Transport', 'amount' => '150.00'],
            ['description' => 'Accommodation', 'amount' => '300.00'],
        ],
    ]);

    $paymentRequest = PaymentRequest::latest()->first();
    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('payment_requests', [
        'type' => 'advance',
        'status' => 'draft',
        'total_amount' => '450.00',
    ]);

    $this->assertDatabaseHas('payment_request_items', ['description' => 'Transport', 'amount' => '150.00']);
    $this->assertDatabaseHas('payment_request_items', ['description' => 'Accommodation', 'amount' => '300.00']);
});
test('store is forbidden when user has no staff profile', function () {
    $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'advance',
        'currency_id' => 1,
        'items' => [['description' => 'Test', 'amount' => '100']],
    ]);

    $response->assertForbidden();
});
test('store fails validation without required fields', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.store'), []);

    $response->assertSessionHasErrors(['type', 'items']);
});
test('store fails validation with invalid type', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'invalid_type',
        'currency_id' => $currency->id,
        'items' => [['description' => 'Test', 'amount' => '100']],
    ]);

    $response->assertSessionHasErrors(['type']);
});
test('store fails validation with empty items array', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'advance',
        'currency_id' => $currency->id,
        'items' => [],
    ]);

    $response->assertSessionHasErrors(['items']);
});
test('store fails validation when item amount is zero', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();

    $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'advance',
        'currency_id' => $currency->id,
        'items' => [['description' => 'Test', 'amount' => '0']],
    ]);

    $response->assertSessionHasErrors(['items.0.amount']);
});
test('show renders payment request', function () {
    $paymentRequest = PaymentRequest::factory()->create(['branch_id' => $this->branch->id]);
    PaymentRequestItem::factory()->create(['payment_request_id' => $paymentRequest->id]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.show', $paymentRequest));

    $response->assertOk();
    $response->assertViewIs('tenant.payment-requests.show');
    $response->assertViewHas('paymentRequest');
});
test('show passes active stage data when workflow is in progress', function () {
    $template = WorkflowTemplate::factory()->advance()->create();
    $stageDef = WorkflowStage::factory()->create(['workflow_template_id' => $template->id, 'display_order' => 1]);
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => $this->branch->id]);

    $instance = WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);
    WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stageDef->id,
        'status' => 'active',
        'started_at' => now(),
    ]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.show', $paymentRequest));

    $response->assertOk();
    $response->assertViewHas('canActOnActiveStage');
    $response->assertSee(__('payment_requests.show.workflow_version', ['version' => $template->version]));
});
test('edit renders for sent back request owner', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'sent_back',
        'staff_id' => $staff->id,
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.edit', $paymentRequest));

    $response->assertOk();
    $response->assertViewIs('tenant.payment-requests.edit');
    $response->assertViewHas(['paymentRequest', 'costCodes']);
});
test('edit renders for draft request owner', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'staff_id' => $staff->id,
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.edit', $paymentRequest));

    $response->assertOk();
    $response->assertViewIs('tenant.payment-requests.edit');
});
test('edit redirects if request is not editable', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'in_workflow',
        'staff_id' => $staff->id,
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.edit', $paymentRequest));

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('error', __('flash.requests.edit_only_sent_back'));
});
test('edit redirects if not owner', function () {
    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'sent_back', 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->get(route('payment-requests.edit', $paymentRequest));

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('error', __('flash.requests.edit_not_owner'));
});
test('update saves changes and redirects to show', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $oldCurrency = Currency::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'sent_back',
        'staff_id' => $staff->id,
        'currency_id' => $oldCurrency->id,
        'total_amount' => 100.00,
        'branch_id' => $this->branch->id,
    ]);
    PaymentRequestItem::factory()->create(['payment_request_id' => $paymentRequest->id, 'amount' => 100.00]);

    $response = $this->actingAs($this->user)->put(route('payment-requests.update', $paymentRequest), [
        'notes' => 'Updated notes',
        'items' => [
            ['description' => 'New item', 'amount' => '250.00'],
            ['description' => 'Second item', 'amount' => '50.00'],
        ],
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('success');

    $this->assertDatabaseHas('payment_requests', [
        'id' => $paymentRequest->id,
        'currency_id' => $oldCurrency->id,
        'notes' => 'Updated notes',
        'total_amount' => '300.00',
        'status' => 'sent_back',
    ]);
    $this->assertDatabaseHas('payment_request_items', ['payment_request_id' => $paymentRequest->id, 'description' => 'New item']);
    $this->assertDatabaseHas('payment_request_items', ['payment_request_id' => $paymentRequest->id, 'description' => 'Second item']);
});
test('update replaces old items', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'sent_back',
        'staff_id' => $staff->id,
        'currency_id' => $currency->id,
        'branch_id' => $this->branch->id,
    ]);
    PaymentRequestItem::factory()->create(['payment_request_id' => $paymentRequest->id, 'description' => 'Old item']);

    $this->actingAs($this->user)->put(route('payment-requests.update', $paymentRequest), [
        'currency_id' => $currency->id,
        'items' => [['description' => 'Brand new item', 'amount' => '75.00']],
    ]);

    $this->assertDatabaseMissing('payment_request_items', ['payment_request_id' => $paymentRequest->id, 'description' => 'Old item']);
    $this->assertDatabaseHas('payment_request_items', ['payment_request_id' => $paymentRequest->id, 'description' => 'Brand new item']);
});
test('update saves changes for draft request', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'staff_id' => $staff->id,
        'currency_id' => $currency->id,
        'branch_id' => $this->branch->id,
    ]);
    PaymentRequestItem::factory()->create(['payment_request_id' => $paymentRequest->id, 'description' => 'Old item']);

    $response = $this->actingAs($this->user)->put(route('payment-requests.update', $paymentRequest), [
        'currency_id' => $currency->id,
        'items' => [['description' => 'Updated item', 'amount' => '150']],
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('success');
    $this->assertDatabaseHas('payment_requests', ['id' => $paymentRequest->id, 'status' => 'draft']);
    $this->assertDatabaseMissing('payment_request_items', ['payment_request_id' => $paymentRequest->id, 'description' => 'Old item']);
    $this->assertDatabaseHas('payment_request_items', ['payment_request_id' => $paymentRequest->id, 'description' => 'Updated item']);
});
test('update rejects non editable request', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'in_workflow',
        'staff_id' => $staff->id,
        'currency_id' => $currency->id,
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)->put(route('payment-requests.update', $paymentRequest), [
        'currency_id' => $currency->id,
        'items' => [['description' => 'Item', 'amount' => '100']],
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('error', __('flash.requests.edit_only_sent_back'));
});
test('update rejects non owner', function () {
    $currency = Currency::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'sent_back',
        'currency_id' => $currency->id,
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)->put(route('payment-requests.update', $paymentRequest), [
        'currency_id' => $currency->id,
        'items' => [['description' => 'Item', 'amount' => '100']],
    ]);

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('error', __('flash.requests.edit_not_owner'));
});
test('update fails validation without items', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'sent_back',
        'staff_id' => $staff->id,
        'currency_id' => $currency->id,
    ]);

    $response = $this->actingAs($this->user)->put(route('payment-requests.update', $paymentRequest), [
        'currency_id' => $currency->id,
        'items' => [],
    ]);

    $response->assertSessionHasErrors('items');
});
test('destroy deletes draft and redirects to index', function () {
    $paymentRequest = PaymentRequest::factory()->create(['status' => 'draft', 'branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->delete(route('payment-requests.destroy', $paymentRequest));

    $response->assertRedirect(route('payment-requests.index'));
    $response->assertSessionHas('success');
    $this->assertSoftDeleted('payment_requests', ['id' => $paymentRequest->id]);
});
test('destroy refuses non draft', function () {
    $paymentRequest = PaymentRequest::factory()->inWorkflow()->create(['branch_id' => $this->branch->id]);

    $response = $this->actingAs($this->user)->delete(route('payment-requests.destroy', $paymentRequest));

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('error');
    $this->assertModelExists($paymentRequest);
});
test('store rejects duplicate receipt number already in payment request items', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    PaymentRequestItem::factory()->create(['receipt_number' => 'RCP-DUPE']);
    $currency = Currency::factory()->create();

    $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'advance',
        'currency_id' => $currency->id,
        'items' => [['description' => 'Transport', 'amount' => '100.00', 'receipt_number' => 'RCP-DUPE']],
    ])->assertSessionHasErrors(['items.0.receipt_number']);
});
test('store rejects receipt number already used in retirement request items', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    RetirementRequestItem::factory()->create(['receipt_number' => 'RCP-CROSS']);
    $currency = Currency::factory()->create();

    $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'advance',
        'currency_id' => $currency->id,
        'items' => [['description' => 'Accommodation', 'amount' => '200.00', 'receipt_number' => 'RCP-CROSS']],
    ])->assertSessionHasErrors(['items.0.receipt_number']);
});
test('store rejects duplicate receipt numbers within same submission', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();

    $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'advance',
        'currency_id' => $currency->id,
        'items' => [
            ['description' => 'Item A', 'amount' => '100.00', 'receipt_number' => 'RCP-SAME'],
            ['description' => 'Item B', 'amount' => '150.00', 'receipt_number' => 'RCP-SAME'],
        ],
    ])->assertSessionHasErrors(['items.0.receipt_number', 'items.1.receipt_number']);
});
test('update allows same receipt numbers for existing request', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'sent_back',
        'staff_id' => $staff->id,
        'currency_id' => $currency->id,
        'branch_id' => $this->branch->id,
    ]);
    PaymentRequestItem::factory()->create([
        'payment_request_id' => $paymentRequest->id,
        'receipt_number' => 'RCP-KEEP',
        'amount' => 100.00,
    ]);

    $this->actingAs($this->user)->put(route('payment-requests.update', $paymentRequest), [
        'currency_id' => $currency->id,
        'items' => [['description' => 'Transport', 'amount' => '100.00', 'receipt_number' => 'RCP-KEEP']],
    ])->assertSessionHasNoErrors()->assertRedirect();
});
test('store allows receipt number from cancelled payment request', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();
    $cancelled = PaymentRequest::factory()->create(['status' => 'cancelled']);
    PaymentRequestItem::factory()->create([
        'payment_request_id' => $cancelled->id,
        'receipt_number' => 'RCP-CANCELLED',
    ]);

    $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'advance',
        'currency_id' => $currency->id,
        'items' => [['description' => 'Transport', 'amount' => '100.00', 'receipt_number' => 'RCP-CANCELLED']],
    ])->assertSessionHasNoErrors();
});
test('store allows receipt number from denied payment request', function () {
    Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
    $currency = Currency::factory()->create();
    $denied = PaymentRequest::factory()->create(['status' => 'denied']);
    PaymentRequestItem::factory()->create([
        'payment_request_id' => $denied->id,
        'receipt_number' => 'RCP-DENIED',
    ]);

    $this->actingAs($this->user)->post(route('payment-requests.store'), [
        'type' => 'advance',
        'currency_id' => $currency->id,
        'items' => [['description' => 'Transport', 'amount' => '100.00', 'receipt_number' => 'RCP-DENIED']],
    ])->assertSessionHasNoErrors();
});
