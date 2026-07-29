<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\Cashbook;
use App\Models\Tenant\CashCount;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementReminderLog;
use App\Models\Tenant\RetirementRequest;

test('guest is redirected from reports index', function () {
    $this->get(route('reports.index'))->assertRedirect(route('login'));
});
test('user without access reports cannot view index', function () {
    $this->role->revokePermissionTo(PermissionKey::AccessReports->value);

    $this->actingAs($this->user)->get(route('reports.index'))->assertForbidden();
});
test('authorized user can view reports index', function () {
    $this->actingAs($this->user)->get(route('reports.index'))->assertOk();
});
// ── Individual report routes ──────────────────────────────────────────────
/* @return array<string, array{string}> */
dataset('reportRouteProvider', fn() => [
    'expenditure summary' => ['reports.expenditure-summary'],
    'outstanding advances' => ['reports.outstanding-advances'],
    'cash position' => ['reports.cash-position'],
    'disbursement register' => ['reports.disbursement-register'],
    'approval turnaround' => ['reports.approval-turnaround'],
    'pending requests aging' => ['reports.pending-requests-aging'],
    'send back rate' => ['reports.send-back-rate'],
    'audit trail' => ['reports.audit-trail'],
    'requests by status' => ['reports.requests-by-status'],
    'workflow sla' => ['reports.workflow-sla'],
    'spend trend' => ['reports.spend-trend'],
    'top spenders' => ['reports.top-spenders'],
    'retirement reminders' => ['reports.retirement-reminders'],
    'retirement variance' => ['reports.retirement-variance'],
    'denied cancelled' => ['reports.denied-cancelled'],
    'retirement turnaround' => ['reports.retirement-turnaround'],
    'cash count' => ['reports.cash-count'],
]);
test('authorized user can access each report route', function (string $routeName) {
    $this->actingAs($this->user)->get(route($routeName))->assertOk();
})->with('reportRouteProvider');
test('each report route returns 403 without permission', function (string $routeName) {
    $this->role->revokePermissionTo(PermissionKey::AccessReports->value);

    $this->actingAs($this->user)->get(route($routeName))->assertForbidden();
})->with('reportRouteProvider');
test('expenditure summary grouped by branch', function () {
    PaymentRequest::factory()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.expenditure-summary', ['group_by' => 'branch']))
        ->assertOk();
});
test('expenditure summary grouped by cost code', function () {
    $this->actingAs($this->user)
        ->get(route('reports.expenditure-summary', ['group_by' => 'cost_code']))
        ->assertOk();
});
test('expenditure summary grouped by department default', function () {
    $this->actingAs($this->user)
        ->get(route('reports.expenditure-summary', ['group_by' => 'department']))
        ->assertOk();
});
test('expenditure summary with type filter', function () {
    PaymentRequest::factory()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'type' => 'expense',
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.expenditure-summary', ['group_by' => 'department', 'type' => 'expense']))
        ->assertOk();
});
test('expenditure summary with date range', function () {
    $this->actingAs($this->user)
        ->get(route('reports.expenditure-summary', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk();
});
test('outstanding advances without branch filter', function () {
    PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(10),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.outstanding-advances'))
        ->assertOk();
});
test('outstanding advances with branch filter', function () {
    $request = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(10),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.outstanding-advances', ['branch_id' => $request->branch_id]))
        ->assertOk();
});
test('disbursement register with branch filter', function () {
    $request = PaymentRequest::factory()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.disbursement-register', ['branch_id' => $request->branch_id]))
        ->assertOk();
});
test('disbursement register with method filter', function () {
    $this->actingAs($this->user)
        ->get(route('reports.disbursement-register', ['method' => 'bank_transfer']))
        ->assertOk();
});
test('disbursement register with branch and method filters', function () {
    $request = PaymentRequest::factory()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.disbursement-register', [
            'branch_id' => $request->branch_id,
            'method' => 'cash',
        ]))
        ->assertOk();
});
test('workflow sla with custom sla days', function () {
    $this->actingAs($this->user)
        ->get(route('reports.workflow-sla', ['sla_days' => 5]))
        ->assertOk();
});
test('workflow sla with type filter', function () {
    PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'submitted_at' => now()->subDays(2),
        'approved_at' => now(),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.workflow-sla', ['type' => 'advance']))
        ->assertOk();
});
test('workflow sla with date range', function () {
    $this->actingAs($this->user)
        ->get(route('reports.workflow-sla', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
            'sla_days' => 3,
        ]))
        ->assertOk();
});
test('spend trend with year filter', function () {
    $this->actingAs($this->user)
        ->get(route('reports.spend-trend', ['year' => now()->year]))
        ->assertOk();
});
test('spend trend with type filter', function () {
    PaymentRequest::factory()->expense()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.spend-trend', ['type' => 'expense']))
        ->assertOk();
});
test('spend trend with data populates years', function () {
    PaymentRequest::factory()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.spend-trend'))
        ->assertOk()
        ->assertViewHas('years');
});
test('top spenders grouped by department', function () {
    PaymentRequest::factory()->create([
        'status' => 'disbursed',
        'disbursed_at' => now(),
        'branch_id' => $this->branch->id,
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.top-spenders', ['group_by' => 'department']))
        ->assertOk();
});
test('top spenders grouped by staff default', function () {
    $this->actingAs($this->user)
        ->get(route('reports.top-spenders', ['group_by' => 'staff']))
        ->assertOk();
});
test('top spenders with type filter', function () {
    $this->actingAs($this->user)
        ->get(route('reports.top-spenders', ['type' => 'advance']))
        ->assertOk();
});
test('audit trail with action filter', function () {
    $this->actingAs($this->user)
        ->get(route('reports.audit-trail', ['action' => 'approved']))
        ->assertOk();
});
test('outstanding advances bucket logic within 30 days', function () {
    PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(10),
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.outstanding-advances'))
        ->assertOk();

    $advances = $response->viewData('advances');
    expect($advances->where('bucket', '0–30 days'))->not->toBeEmpty();
});
test('outstanding advances bucket logic 31 to 60 days', function () {
    PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(45),
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.outstanding-advances'))
        ->assertOk();

    $advances = $response->viewData('advances');
    expect($advances->where('bucket', '31–60 days'))->not->toBeEmpty();
});
test('outstanding advances bucket logic 61 plus days', function () {
    PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(90),
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.outstanding-advances'))
        ->assertOk();

    $advances = $response->viewData('advances');
    expect($advances->where('bucket', '61+ days'))->not->toBeEmpty();
});
test('approval turnaround with completed stages returns mapped data', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'name' => 'Finance Review',
    ]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);

    App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'approved',
        'started_at' => now()->subHours(3),
        'completed_at' => now()->subHour(),
    ]);

    $dateTo = now()->addDay()->toDateString();

    $response = $this->actingAs($this->user)
        ->get(route('reports.approval-turnaround', ['date_to' => $dateTo]))
        ->assertOk();

    $stages = $response->viewData('stages');
    expect($stages)->not->toBeEmpty();
    expect($stages->first())->toHaveKey('stage_name');
    expect($stages->first())->toHaveKey('avg_hours');
});
test('approval turnaround includes sent back stages', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'expense']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'name' => 'Director Review',
    ]);

    $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);

    App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'sent_back',
        'started_at' => now()->subHours(2),
        'completed_at' => now()->subMinutes(30),
    ]);

    $dateTo = now()->addDay()->toDateString();

    $response = $this->actingAs($this->user)
        ->get(route('reports.approval-turnaround', ['date_to' => $dateTo]))
        ->assertOk();

    $stages = $response->viewData('stages');
    expect($stages)->not->toBeEmpty();
    $sentBackCount = $stages->first()['sent_back'];
    expect($sentBackCount)->toEqual(1);
});
test('pending requests aging with active stages returns buckets', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'name' => 'Manager Approval',
    ]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);

    App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'active',
        'started_at' => now()->subDays(2),
        'completed_at' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.pending-requests-aging'))
        ->assertOk();

    $activeStages = $response->viewData('activeStages');
    expect($activeStages)->not->toBeEmpty();
    expect($activeStages->first()['bucket'])->toEqual('0–3 days');
});
test('pending requests aging bucket 4 to 7 days', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'expense']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
    ]);

    $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);

    App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'active',
        'started_at' => now()->subDays(5),
        'completed_at' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.pending-requests-aging'))
        ->assertOk();

    $activeStages = $response->viewData('activeStages');
    expect($activeStages->where('bucket', '4–7 days'))->not->toBeEmpty();
});
test('pending requests aging bucket 8 to 14 days', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
    ]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);

    App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'active',
        'started_at' => now()->subDays(10),
        'completed_at' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.pending-requests-aging'))
        ->assertOk();

    $activeStages = $response->viewData('activeStages');
    expect($activeStages->where('bucket', '8–14 days'))->not->toBeEmpty();
});
test('pending requests aging bucket 15 plus days', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
    ]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);

    App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'active',
        'started_at' => now()->subDays(20),
        'completed_at' => null,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.pending-requests-aging'))
        ->assertOk();

    $activeStages = $response->viewData('activeStages');
    expect($activeStages->where('bucket', '15+ days'))->not->toBeEmpty();
});
test('send back rate with workflow actions calculates rate', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
    ]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => $this->branch->id]);
    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);

    $instanceStage = App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'approved',
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    App\Models\Tenant\WorkflowAction::create([
        'workflow_instance_stage_id' => $instanceStage->id,
        'user_id' => $this->user->id,
        'action' => 'approved',
        'comment' => null,
        'created_at' => now(),
    ]);

    App\Models\Tenant\WorkflowAction::create([
        'workflow_instance_stage_id' => $instanceStage->id,
        'user_id' => $this->user->id,
        'action' => 'sent_back',
        'comment' => 'Missing receipts',
        'created_at' => now(),
    ]);

    $dateTo = now()->addDay()->toDateString();

    $response = $this->actingAs($this->user)
        ->get(route('reports.send-back-rate', ['date_to' => $dateTo]))
        ->assertOk();

    $rows = $response->viewData('rows');
    expect($rows)->not->toBeEmpty();
    $userRow = $rows->firstWhere('user.id', $this->user->id);
    expect($userRow)->not->toBeNull();
    expect($userRow['total_actions'])->toEqual(2);
    expect($userRow['sent_back_count'])->toEqual(1);
    expect($userRow['rate'])->toEqual(50.0);
});
test('audit trail with workflow actions and date filter', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
    ]);

    $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => $this->branch->id]);
    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);

    $instanceStage = App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'approved',
        'started_at' => now()->subHour(),
        'completed_at' => now(),
    ]);

    App\Models\Tenant\WorkflowAction::create([
        'workflow_instance_stage_id' => $instanceStage->id,
        'user_id' => $this->user->id,
        'action' => 'approved',
        'comment' => 'Looks good',
        'created_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.audit-trail', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk()
        ->assertViewHas('actions');
});
test('spend trend uses current year fallback when no disbursed requests', function () {
    $response = $this->actingAs($this->user)
        ->get(route('reports.spend-trend'))
        ->assertOk();

    $years = $response->viewData('years');
    expect($years->toArray())->toContain(now()->year);
});
test('cash position renders with cashbook and entry data', function () {
    $currency = App\Models\Tenant\Currency::factory()->create();
    $cashbook = Cashbook::create([
        'branch_id' => $this->branch->id,
        'currency_id' => $currency->id,
        'balance' => '1000.00',
    ]);

    App\Models\Tenant\CashbookEntry::create([
        'cashbook_id' => $cashbook->id,
        'type' => 'credit',
        'amount' => '500.00',
        'description' => 'Opening balance',
        'entry_date' => now()->toDateString(),
    ]);

    App\Models\Tenant\CashbookEntry::create([
        'cashbook_id' => $cashbook->id,
        'type' => 'debit',
        'amount' => '200.00',
        'description' => 'Expense payment',
        'entry_date' => now()->toDateString(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.cash-position'))
        ->assertOk();

    $cashbooks = $response->viewData('cashbooks');
    expect($cashbooks)->not->toBeEmpty();
    $first = $cashbooks->first();
    expect($first['period_credits'])->toEqual('500.00');
    expect($first['period_debits'])->toEqual('200.00');
    expect($first['entry_count'])->toEqual(2);
});
test('workflow sla calculates compliance rate correctly', function () {
    // compliant: approved within 3 days
    PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'submitted_at' => now()->subDays(2),
        'approved_at' => now()->subDay(),
        'branch_id' => $this->branch->id,
    ]);

    // non-compliant: approved after 5 days
    PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'submitted_at' => now()->subDays(6),
        'approved_at' => now()->subDay(),
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.workflow-sla', ['sla_days' => 3]))
        ->assertOk();

    $complianceRate = $response->viewData('complianceRate');
    $total = $response->viewData('total');
    expect($total)->toEqual(2);
    expect($complianceRate)->toEqual(50.0);
});
test('retirement reminders empty state returns 200', function () {
    $this->actingAs($this->user)
        ->get(route('reports.retirement-reminders'))
        ->assertOk();
});
test('retirement reminders shows advance with reminder logs', function () {
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(30),
        'branch_id' => $this->branch->id,
    ]);

    RetirementReminderLog::create([
        'payment_request_id' => $advance->id,
        'user_id' => $this->user->id,
        'notified_date' => now()->subDays(2)->toDateString(),
    ]);

    RetirementReminderLog::create([
        'payment_request_id' => $advance->id,
        'user_id' => $this->user->id,
        'notified_date' => now()->subDays(1)->toDateString(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.retirement-reminders', [
            'date_from' => now()->subMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk();

    $rows = $response->viewData('rows');
    expect($rows)->toHaveCount(1);
    expect($rows->first()->reminder_count)->toEqual(2);
    expect($rows->first()->last_reminder_date)->toEqual(now()->subDays(1)->toDateString());
});
test('requests by status with date filter returns 200', function () {
    PaymentRequest::factory()->create([
        'status' => 'disbursed',
        'branch_id' => $this->branch->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.requests-by-status', [
            'date_from' => now()->startOfYear()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk();

    expect($response->viewData('dateFrom'))->not->toBeNull();
    expect($response->viewData('dateTo'))->not->toBeNull();
});
test('requests by status date filter excludes out of range requests', function () {
    PaymentRequest::factory()->create([
        'status' => 'approved',
        'branch_id' => $this->branch->id,
        'created_at' => now()->subYears(2),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.requests-by-status', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk();

    $paymentStatuses = $response->viewData('paymentStatuses');
    $found = $paymentStatuses->firstWhere('status', 'approved');
    expect($found === null || (int) $found->count === 0)->toBeTrue();
});
test('retirement variance empty state returns 200', function () {
    $this->actingAs($this->user)
        ->get(route('reports.retirement-variance'))
        ->assertOk()
        ->assertViewHas('rows');
});
test('retirement variance shows approved retirement row', function () {
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(10),
        'total_amount' => 500.00,
        'branch_id' => $this->branch->id,
    ]);

    RetirementRequest::factory()->approved()->create([
        'payment_request_id' => $advance->id,
        'total_amount_expended' => 420.00,
        'difference_amount' => 80.00,
        'difference_type' => 'refund_to_company',
        'approved_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.retirement-variance', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]))
        ->assertOk();

    $rows = $response->viewData('rows');
    expect($rows)->toHaveCount(1);
    expect($rows->first()->payment_request_id)->toEqual($advance->id);
    expect($rows->first()->total_amount_expended)->toEqual('420.00');
});
test('retirement variance excludes unapproved retirements', function () {
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(5),
        'branch_id' => $this->branch->id,
    ]);

    RetirementRequest::factory()->inWorkflow()->create([
        'payment_request_id' => $advance->id,
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.retirement-variance', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]))
        ->assertOk();

    $rows = $response->viewData('rows');
    expect($rows)->toHaveCount(0);
});
test('retirement variance with branch filter', function () {
    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(5),
        'branch_id' => $this->branch->id,
    ]);

    RetirementRequest::factory()->approved()->create([
        'payment_request_id' => $advance->id,
        'approved_at' => now(),
    ]);

    $this->actingAs($this->user)
        ->get(route('reports.retirement-variance', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
            'branch_id' => $this->branch->id,
        ]))
        ->assertOk();
});
test('denied cancelled empty state returns 200', function () {
    $this->actingAs($this->user)
        ->get(route('reports.denied-cancelled'))
        ->assertOk()
        ->assertViewHas('rows')
        ->assertViewHas('deniedCount')
        ->assertViewHas('cancelledCount');
});
test('denied cancelled counts denied requests', function () {
    PaymentRequest::factory()->advance()->create([
        'status' => 'denied',
        'branch_id' => $this->branch->id,
        'updated_at' => now(),
    ]);

    PaymentRequest::factory()->advance()->create([
        'status' => 'denied',
        'branch_id' => $this->branch->id,
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.denied-cancelled', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]))
        ->assertOk();

    expect($response->viewData('deniedCount'))->toEqual(2);
    expect($response->viewData('cancelledCount'))->toEqual(0);
});
test('denied cancelled counts cancelled requests', function () {
    PaymentRequest::factory()->expense()->create([
        'status' => 'cancelled',
        'branch_id' => $this->branch->id,
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.denied-cancelled', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]))
        ->assertOk();

    expect($response->viewData('deniedCount'))->toEqual(0);
    expect($response->viewData('cancelledCount'))->toEqual(1);
});
test('denied cancelled with type filter', function () {
    PaymentRequest::factory()->advance()->create([
        'status' => 'denied',
        'branch_id' => $this->branch->id,
        'updated_at' => now(),
    ]);

    PaymentRequest::factory()->expense()->create([
        'status' => 'denied',
        'branch_id' => $this->branch->id,
        'updated_at' => now(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.denied-cancelled', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
            'type' => 'advance',
        ]))
        ->assertOk();

    expect($response->viewData('deniedCount'))->toEqual(1);
});
test('retirement turnaround empty state returns 200', function () {
    $this->actingAs($this->user)
        ->get(route('reports.retirement-turnaround'))
        ->assertOk()
        ->assertViewHas('stages');
});
test('retirement turnaround with completed stages returns mapped data', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'name' => 'Finance Retirement Review',
    ]);

    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(10),
        'branch_id' => $this->branch->id,
    ]);

    $retirement = RetirementRequest::factory()->inWorkflow()->create([
        'payment_request_id' => $advance->id,
    ]);

    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => RetirementRequest::class,
        'workflowable_id' => $retirement->id,
        'status' => 'in_progress',
    ]);

    App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'approved',
        'started_at' => now()->subHours(4),
        'completed_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.retirement-turnaround', [
            'date_to' => now()->addDay()->toDateString(),
        ]))
        ->assertOk();

    $stages = $response->viewData('stages');
    expect($stages)->not->toBeEmpty();
    expect($stages->first()['stage_name'])->toEqual('Finance Retirement Review');
    expect($stages->first())->toHaveKey('avg_hours');
});
test('retirement turnaround excludes payment request workflow stages', function () {
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
    $stage = App\Models\Tenant\WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'name' => 'Payment Approval',
    ]);

    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'in_workflow',
        'branch_id' => $this->branch->id,
    ]);

    $instance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
    ]);

    App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $instance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'approved',
        'started_at' => now()->subHours(2),
        'completed_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.retirement-turnaround', [
            'date_to' => now()->addDay()->toDateString(),
        ]))
        ->assertOk();

    $stages = $response->viewData('stages');
    expect($stages)->toBeEmpty();
});
test('retirement reminders branch filter excludes other branches', function () {
    $otherBranch = App\Models\Tenant\Branch::factory()->create();

    $advance = PaymentRequest::factory()->advance()->create([
        'status' => 'disbursed',
        'disbursed_at' => now()->subDays(15),
        'branch_id' => $otherBranch->id,
    ]);

    RetirementReminderLog::create([
        'payment_request_id' => $advance->id,
        'user_id' => $this->user->id,
        'notified_date' => now()->toDateString(),
    ]);

    // User's allowed branch scope only includes $this->branch
    $response = $this->actingAs($this->user)
        ->get(route('reports.retirement-reminders', [
            'date_from' => now()->subMonth()->toDateString(),
            'date_to' => now()->addDay()->toDateString(),
        ]))
        ->assertOk();

    $rows = $response->viewData('rows');
    expect($rows)->toHaveCount(0);
});
test('cash count report empty state returns 200', function () {
    $this->actingAs($this->user)
        ->get(route('reports.cash-count'))
        ->assertOk()
        ->assertViewHas('rows')
        ->assertViewHas('totalCounts')
        ->assertViewHas('withDiscrepancy')
        ->assertViewHas('netDifference');
});
test('cash count report shows counts in date range', function () {
    $currency = App\Models\Tenant\Currency::factory()->create();
    $cashbook = Cashbook::firstOrCreate(
        ['branch_id' => $this->branch->id],
        ['currency_id' => $currency->id, 'balance' => 1000],
    );

    CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => '1000.00',
        'counted_total' => '1000.00',
        'difference' => '0.00',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.cash-count', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk();

    expect($response->viewData('totalCounts'))->toEqual(1);
    expect($response->viewData('withDiscrepancy'))->toEqual(0);
    expect($response->viewData('netDifference'))->toEqual(0.0);
});
test('cash count report counts discrepancies correctly', function () {
    $currency = App\Models\Tenant\Currency::factory()->create();
    $cashbook = Cashbook::firstOrCreate(
        ['branch_id' => $this->branch->id],
        ['currency_id' => $currency->id, 'balance' => 1000],
    );

    // Balanced count
    CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => '500.00',
        'counted_total' => '500.00',
        'difference' => '0.00',
    ]);

    // Surplus count
    CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now()->subDay(),
        'cashbook_balance_at_count' => '500.00',
        'counted_total' => '550.00',
        'difference' => '50.00',
    ]);

    // Deficit count
    CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now()->subDays(2),
        'cashbook_balance_at_count' => '500.00',
        'counted_total' => '480.00',
        'difference' => '-20.00',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.cash-count', [
            'date_from' => now()->subWeek()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk();

    expect($response->viewData('totalCounts'))->toEqual(3);
    expect($response->viewData('withDiscrepancy'))->toEqual(2);
    expect($response->viewData('netDifference'))->toEqual(30.0);
});
test('cash count report excludes counts outside date range', function () {
    $currency = App\Models\Tenant\Currency::factory()->create();
    $cashbook = Cashbook::firstOrCreate(
        ['branch_id' => $this->branch->id],
        ['currency_id' => $currency->id, 'balance' => 1000],
    );

    CashCount::create([
        'cashbook_id' => $cashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now()->subYears(2),
        'cashbook_balance_at_count' => '1000.00',
        'counted_total' => '1000.00',
        'difference' => '0.00',
    ]);

    $response = $this->actingAs($this->user)
        ->get(route('reports.cash-count', [
            'date_from' => now()->startOfMonth()->toDateString(),
            'date_to' => now()->toDateString(),
        ]))
        ->assertOk();

    expect($response->viewData('totalCounts'))->toEqual(0);
});
test('cash count report branch filter excludes other branches', function () {
    $otherBranch = App\Models\Tenant\Branch::factory()->create();
    $currency = App\Models\Tenant\Currency::factory()->create();
    $otherCashbook = Cashbook::firstOrCreate(
        ['branch_id' => $otherBranch->id],
        ['currency_id' => $currency->id, 'balance' => 0],
    );

    CashCount::create([
        'cashbook_id' => $otherCashbook->id,
        'counted_by_user_id' => $this->user->id,
        'counted_at' => now(),
        'cashbook_balance_at_count' => '1000.00',
        'counted_total' => '1000.00',
        'difference' => '0.00',
    ]);

    // User's allowed branch scope only includes $this->branch
    $response = $this->actingAs($this->user)
        ->get(route('reports.cash-count'))
        ->assertOk();

    expect($response->viewData('totalCounts'))->toEqual(0);
});
