<?php

declare(strict_types=1);

namespace Tests\Feature\Reports;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\PaymentRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TenantAppTestCase;

class ReportsControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_reports_index(): void
    {
        $this->get(route('reports.index'))->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_access_reports_cannot_view_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessReports->value);

        $this->actingAs($this->user)->get(route('reports.index'))->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorized_user_can_view_reports_index(): void
    {
        $this->actingAs($this->user)->get(route('reports.index'))->assertOk();
    }

    // ── Individual report routes ──────────────────────────────────────────────

    /** @return array<string, array{string}> */
    public static function reportRouteProvider(): array
    {
        return [
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
        ];
    }

    #[DataProvider('reportRouteProvider')]
    public function test_authorized_user_can_access_each_report_route(string $routeName): void
    {
        $this->actingAs($this->user)->get(route($routeName))->assertOk();
    }

    #[DataProvider('reportRouteProvider')]
    public function test_each_report_route_returns_403_without_permission(string $routeName): void
    {
        $this->role->revokePermissionTo(PermissionKey::AccessReports->value);

        $this->actingAs($this->user)->get(route($routeName))->assertForbidden();
    }

    // ── expenditureSummary filter variants ───────────────────────────────────

    public function test_expenditure_summary_grouped_by_branch(): void
    {
        PaymentRequest::factory()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.expenditure-summary', ['group_by' => 'branch']))
            ->assertOk();
    }

    public function test_expenditure_summary_grouped_by_account_code(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.expenditure-summary', ['group_by' => 'account_code']))
            ->assertOk();
    }

    public function test_expenditure_summary_grouped_by_department_default(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.expenditure-summary', ['group_by' => 'department']))
            ->assertOk();
    }

    public function test_expenditure_summary_with_type_filter(): void
    {
        PaymentRequest::factory()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'type' => 'expense',
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.expenditure-summary', ['group_by' => 'department', 'type' => 'expense']))
            ->assertOk();
    }

    public function test_expenditure_summary_with_date_range(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.expenditure-summary', [
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->toDateString(),
            ]))
            ->assertOk();
    }

    // ── outstandingAdvances filter variants ──────────────────────────────────

    public function test_outstanding_advances_without_branch_filter(): void
    {
        PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now()->subDays(10),
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.outstanding-advances'))
            ->assertOk();
    }

    public function test_outstanding_advances_with_branch_filter(): void
    {
        $request = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now()->subDays(10),
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.outstanding-advances', ['branch_id' => $request->branch_id]))
            ->assertOk();
    }

    // ── disbursementRegister filter variants ─────────────────────────────────

    public function test_disbursement_register_with_branch_filter(): void
    {
        $request = PaymentRequest::factory()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.disbursement-register', ['branch_id' => $request->branch_id]))
            ->assertOk();
    }

    public function test_disbursement_register_with_method_filter(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.disbursement-register', ['method' => 'bank_transfer']))
            ->assertOk();
    }

    public function test_disbursement_register_with_branch_and_method_filters(): void
    {
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
    }

    // ── workflowSla filter variants ──────────────────────────────────────────

    public function test_workflow_sla_with_custom_sla_days(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.workflow-sla', ['sla_days' => 5]))
            ->assertOk();
    }

    public function test_workflow_sla_with_type_filter(): void
    {
        PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'submitted_at' => now()->subDays(2),
            'approved_at' => now(),
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.workflow-sla', ['type' => 'advance']))
            ->assertOk();
    }

    public function test_workflow_sla_with_date_range(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.workflow-sla', [
                'date_from' => now()->startOfMonth()->toDateString(),
                'date_to' => now()->toDateString(),
                'sla_days' => 3,
            ]))
            ->assertOk();
    }

    // ── spendTrend filter variants ───────────────────────────────────────────

    public function test_spend_trend_with_year_filter(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.spend-trend', ['year' => now()->year]))
            ->assertOk();
    }

    public function test_spend_trend_with_type_filter(): void
    {
        PaymentRequest::factory()->expense()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.spend-trend', ['type' => 'expense']))
            ->assertOk();
    }

    public function test_spend_trend_with_data_populates_years(): void
    {
        PaymentRequest::factory()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.spend-trend'))
            ->assertOk()
            ->assertViewHas('years');
    }

    // ── topSpenders filter variants ──────────────────────────────────────────

    public function test_top_spenders_grouped_by_department(): void
    {
        PaymentRequest::factory()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user)
            ->get(route('reports.top-spenders', ['group_by' => 'department']))
            ->assertOk();
    }

    public function test_top_spenders_grouped_by_staff_default(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.top-spenders', ['group_by' => 'staff']))
            ->assertOk();
    }

    public function test_top_spenders_with_type_filter(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.top-spenders', ['type' => 'advance']))
            ->assertOk();
    }

    // ── auditTrail filter variants ───────────────────────────────────────────

    public function test_audit_trail_with_action_filter(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.audit-trail', ['action' => 'approved']))
            ->assertOk();
    }

    // ── outstandingAdvances bucket logic ─────────────────────────────────────

    public function test_outstanding_advances_bucket_logic_within_30_days(): void
    {
        PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now()->subDays(10),
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.outstanding-advances'))
            ->assertOk();

        $advances = $response->viewData('advances');
        $this->assertNotEmpty($advances->where('bucket', '0–30 days'));
    }

    public function test_outstanding_advances_bucket_logic_31_to_60_days(): void
    {
        PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now()->subDays(45),
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.outstanding-advances'))
            ->assertOk();

        $advances = $response->viewData('advances');
        $this->assertNotEmpty($advances->where('bucket', '31–60 days'));
    }

    public function test_outstanding_advances_bucket_logic_61_plus_days(): void
    {
        PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now()->subDays(90),
            'branch_id' => $this->branch->id,
        ]);

        $response = $this->actingAs($this->user)
            ->get(route('reports.outstanding-advances'))
            ->assertOk();

        $advances = $response->viewData('advances');
        $this->assertNotEmpty($advances->where('bucket', '61+ days'));
    }

    // ── approvalTurnaround with actual data ──────────────────────────────────

    public function test_approval_turnaround_with_completed_stages_returns_mapped_data(): void
    {
        $template = \App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
        $stage = \App\Models\Tenant\WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'name' => 'Finance Review',
        ]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
        $instance = \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        \App\Models\Tenant\WorkflowInstanceStage::create([
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
        $this->assertNotEmpty($stages);
        $this->assertArrayHasKey('stage_name', $stages->first());
        $this->assertArrayHasKey('avg_hours', $stages->first());
    }

    public function test_approval_turnaround_includes_sent_back_stages(): void
    {
        $template = \App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'expense']);
        $stage = \App\Models\Tenant\WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'name' => 'Director Review',
        ]);

        $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
        $instance = \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        \App\Models\Tenant\WorkflowInstanceStage::create([
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
        $this->assertNotEmpty($stages);
        $sentBackCount = $stages->first()['sent_back'];
        $this->assertEquals(1, $sentBackCount);
    }

    // ── pendingRequestsAging bucket logic ────────────────────────────────────

    public function test_pending_requests_aging_with_active_stages_returns_buckets(): void
    {
        $template = \App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
        $stage = \App\Models\Tenant\WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'name' => 'Manager Approval',
        ]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
        $instance = \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        \App\Models\Tenant\WorkflowInstanceStage::create([
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
        $this->assertNotEmpty($activeStages);
        $this->assertEquals('0–3 days', $activeStages->first()['bucket']);
    }

    public function test_pending_requests_aging_bucket_4_to_7_days(): void
    {
        $template = \App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'expense']);
        $stage = \App\Models\Tenant\WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
        ]);

        $paymentRequest = PaymentRequest::factory()->expense()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
        $instance = \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        \App\Models\Tenant\WorkflowInstanceStage::create([
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
        $this->assertNotEmpty($activeStages->where('bucket', '4–7 days'));
    }

    public function test_pending_requests_aging_bucket_8_to_14_days(): void
    {
        $template = \App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
        $stage = \App\Models\Tenant\WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
        ]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
        $instance = \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        \App\Models\Tenant\WorkflowInstanceStage::create([
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
        $this->assertNotEmpty($activeStages->where('bucket', '8–14 days'));
    }

    public function test_pending_requests_aging_bucket_15_plus_days(): void
    {
        $template = \App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
        $stage = \App\Models\Tenant\WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
        ]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'submitted_at' => now(), 'branch_id' => $this->branch->id]);
        $instance = \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        \App\Models\Tenant\WorkflowInstanceStage::create([
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
        $this->assertNotEmpty($activeStages->where('bucket', '15+ days'));
    }

    // ── sendBackRate with actual data ────────────────────────────────────────

    public function test_send_back_rate_with_workflow_actions_calculates_rate(): void
    {
        $template = \App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
        $stage = \App\Models\Tenant\WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
        ]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => $this->branch->id]);
        $instance = \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        $instanceStage = \App\Models\Tenant\WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'approved',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        \App\Models\Tenant\WorkflowAction::create([
            'workflow_instance_stage_id' => $instanceStage->id,
            'user_id' => $this->user->id,
            'action' => 'approved',
            'comment' => null,
            'created_at' => now(),
        ]);

        \App\Models\Tenant\WorkflowAction::create([
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
        $this->assertNotEmpty($rows);
        $userRow = $rows->firstWhere('user.id', $this->user->id);
        $this->assertNotNull($userRow);
        $this->assertEquals(2, $userRow['total_actions']);
        $this->assertEquals(1, $userRow['sent_back_count']);
        $this->assertEquals(50.0, $userRow['rate']);
    }

    // ── auditTrail with actual data ──────────────────────────────────────────

    public function test_audit_trail_with_workflow_actions_and_date_filter(): void
    {
        $template = \App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
        $stage = \App\Models\Tenant\WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
        ]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'in_workflow', 'branch_id' => $this->branch->id]);
        $instance = \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
        ]);

        $instanceStage = \App\Models\Tenant\WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'approved',
            'started_at' => now()->subHour(),
            'completed_at' => now(),
        ]);

        \App\Models\Tenant\WorkflowAction::create([
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
    }

    // ── spendTrend empty fallback ─────────────────────────────────────────────

    public function test_spend_trend_uses_current_year_fallback_when_no_disbursed_requests(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.spend-trend'))
            ->assertOk();

        $years = $response->viewData('years');
        $this->assertContains(now()->year, $years->toArray());
    }

    // ── cashPosition with cashbook data ─────────────────────────────────────

    public function test_cash_position_renders_with_cashbook_and_entry_data(): void
    {
        $currency = \App\Models\Tenant\Currency::factory()->create();
        $cashbook = \App\Models\Tenant\Cashbook::create([
            'branch_id' => $this->branch->id,
            'currency_id' => $currency->id,
            'balance' => '1000.00',
        ]);

        \App\Models\Tenant\CashbookEntry::create([
            'cashbook_id' => $cashbook->id,
            'type' => 'credit',
            'amount' => '500.00',
            'description' => 'Opening balance',
            'entry_date' => now()->toDateString(),
        ]);

        \App\Models\Tenant\CashbookEntry::create([
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
        $this->assertNotEmpty($cashbooks);
        $first = $cashbooks->first();
        $this->assertEquals('500.00', $first['period_credits']);
        $this->assertEquals('200.00', $first['period_debits']);
        $this->assertEquals(2, $first['entry_count']);
    }

    // ── workflowSla map logic with data ─────────────────────────────────────

    public function test_workflow_sla_calculates_compliance_rate_correctly(): void
    {
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
        $this->assertEquals(2, $total);
        $this->assertEquals(50.0, $complianceRate);
    }
}
