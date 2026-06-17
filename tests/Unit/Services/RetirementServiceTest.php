<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTOs\Tenant\CreateRetirementRequestDto;
use App\DTOs\Tenant\RetirementRequestItemDto;
use App\Models\Tenant\Branch;
use App\Models\Tenant\CostCode;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\RetirementService;
use Tests\TenantAppTestCase;

class RetirementServiceTest extends TenantAppTestCase
{
    private function makeService(): RetirementService
    {
        return app(RetirementService::class);
    }

    private function makeDto(float $amount = 500.0, string|null $notes = null, bool $didNotSpendMoney = false): CreateRetirementRequestDto
    {
        $costCode = CostCode::factory()->create();

        return new CreateRetirementRequestDto(
            notes: $notes,
            didNotSpendMoney: $didNotSpendMoney,
            items: [
                new RetirementRequestItemDto(
                    description: 'Test item',
                    amount: $amount,
                    costCodeId: $costCode->id,
                    receiptNumber: null,
                ),
            ],
        );
    }

    private function disbursedAdvance(float $totalAmount = 500.0): PaymentRequest
    {
        return PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'total_amount' => $totalAmount,
        ]);
    }

    // ── cancel() without active workflow instance ─────────────────────────────

    public function test_cancel_without_active_instance_sets_status_to_cancelled(): void
    {
        $paymentRequest = $this->disbursedAdvance();
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'draft',
        ]);

        $this->makeService()->cancel($retirement, $this->user);

        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'status' => 'cancelled',
        ]);
    }

    // ── cancel() with active workflow instance ────────────────────────────────

    public function test_cancel_with_active_instance_cancels_instance_and_stages(): void
    {
        $template = WorkflowTemplate::factory()->retirement()->create();
        $stageDef = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);

        $paymentRequest = $this->disbursedAdvance();
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'in_workflow',
        ]);

        $instance = WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => RetirementRequest::class,
            'workflowable_id' => $retirement->id,
            'status' => 'in_progress',
        ]);

        WorkflowInstanceStage::create([
            'workflow_instance_id' => $instance->id,
            'workflow_stage_id' => $stageDef->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->makeService()->cancel($retirement, $this->user);

        $this->assertDatabaseHas('retirement_requests', ['id' => $retirement->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('workflow_instances', ['id' => $instance->id, 'status' => 'cancelled']);
        $this->assertDatabaseHas('workflow_instance_stages', [
            'workflow_instance_id' => $instance->id,
            'status' => 'cancelled',
        ]);
    }

    // ── createDraft() nil difference type ────────────────────────────────────

    public function test_create_draft_sets_nil_difference_type_when_amounts_match(): void
    {
        $paymentRequest = $this->disbursedAdvance(500.0);

        $retirement = $this->makeService()->createDraft($paymentRequest, $this->makeDto(500.0), $this->user);

        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'difference_type' => 'nil',
            'difference_amount' => '0.00',
        ]);
    }

    public function test_create_draft_sets_pay_to_staff_when_expended_exceeds_advance(): void
    {
        $paymentRequest = $this->disbursedAdvance(500.0);

        $retirement = $this->makeService()->createDraft($paymentRequest, $this->makeDto(600.0), $this->user);

        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'difference_type' => 'pay_to_staff',
            'difference_amount' => '100.00',
        ]);
    }

    public function test_create_draft_sets_refund_to_company_when_expended_is_less_than_advance(): void
    {
        $paymentRequest = $this->disbursedAdvance(500.0);

        $retirement = $this->makeService()->createDraft($paymentRequest, $this->makeDto(300.0), $this->user);

        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'difference_type' => 'refund_to_company',
            'difference_amount' => '200.00',
        ]);
    }

    public function test_create_draft_throws_validation_exception_when_retirement_already_exists(): void
    {
        $paymentRequest = $this->disbursedAdvance(500.0);

        // Create the first retirement
        $this->makeService()->createDraft($paymentRequest, $this->makeDto(500.0), $this->user);

        $this->expectException(\Illuminate\Validation\ValidationException::class);

        // Second attempt should fail
        $this->makeService()->createDraft($paymentRequest, $this->makeDto(500.0), $this->user);
    }

    public function test_create_draft_duplicate_check_ignores_cancelled_retirements(): void
    {
        $paymentRequest = $this->disbursedAdvance(500.0);

        // Create and cancel a retirement
        $existing = RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'cancelled',
        ]);

        // A new retirement for the same advance should be allowed
        $retirement = $this->makeService()->createDraft($paymentRequest, $this->makeDto(500.0), $this->user);

        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'status' => 'draft',
        ]);
    }

    public function test_create_draft_can_record_a_no_spend_retirement(): void
    {
        $paymentRequest = $this->disbursedAdvance(500.0);

        $retirement = $this->makeService()->createDraft(
            $paymentRequest,
            new CreateRetirementRequestDto(
                notes: null,
                didNotSpendMoney: true,
                items: [],
            ),
            $this->user,
        );

        $this->assertDatabaseHas('retirement_requests', [
            'id' => $retirement->id,
            'no_money_spent' => 1,
            'total_amount_expended' => '0.00',
            'difference_amount' => '500.00',
            'difference_type' => 'refund_to_company',
        ]);
    }

    // ── updateDraft() nil difference type ────────────────────────────────────

    public function test_update_draft_sets_nil_when_amounts_match(): void
    {
        $paymentRequest = $this->disbursedAdvance(800.0);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'draft',
            'difference_type' => 'refund_to_company',
        ]);

        $updated = $this->makeService()->updateDraft($retirement, $this->makeDto(800.0), $this->user);

        $this->assertSame('nil', $updated->difference_type);
    }

    // ── updateSentBack() nil difference type ─────────────────────────────────

    public function test_update_sent_back_sets_nil_when_amounts_match(): void
    {
        $paymentRequest = $this->disbursedAdvance(300.0);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'sent_back',
            'difference_type' => 'pay_to_staff',
        ]);

        $updated = $this->makeService()->updateSentBack($retirement, $this->makeDto(300.0), $this->user);

        $this->assertSame('nil', $updated->difference_type);
    }

    // ── submit() — branch-specific template selection ─────────────────────────

    public function test_submit_uses_branch_specific_template_when_available(): void
    {
        $branch = Branch::factory()->create();
        $masterTemplate = WorkflowTemplate::factory()->retirement()->create(['branch_id' => null]);
        $branchTemplate = WorkflowTemplate::factory()->retirement()->create(['branch_id' => $branch->id]);

        WorkflowStage::factory()->create(['workflow_template_id' => $branchTemplate->id, 'display_order' => 1]);

        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'branch_id' => $branch->id,
            'total_amount' => 500.0,
        ]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'draft',
        ]);

        $this->makeService()->submit($retirement, $this->user);

        $instance = WorkflowInstance::where('workflowable_id', $retirement->id)
            ->where('workflowable_type', RetirementRequest::class)
            ->firstOrFail();

        $this->assertEquals($branchTemplate->id, $instance->workflow_template_id);
        $this->assertNotEquals($masterTemplate->id, $instance->workflow_template_id);
    }

    public function test_submit_falls_back_to_master_template_when_no_branch_template(): void
    {
        $branch = Branch::factory()->create();
        $masterTemplate = WorkflowTemplate::factory()->retirement()->create(['branch_id' => null]);

        WorkflowStage::factory()->create(['workflow_template_id' => $masterTemplate->id, 'display_order' => 1]);

        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'branch_id' => $branch->id,
            'total_amount' => 500.0,
        ]);
        $retirement = RetirementRequest::factory()->create([
            'payment_request_id' => $paymentRequest->id,
            'status' => 'draft',
        ]);

        $this->makeService()->submit($retirement, $this->user);

        $instance = WorkflowInstance::where('workflowable_id', $retirement->id)
            ->where('workflowable_type', RetirementRequest::class)
            ->firstOrFail();

        $this->assertEquals($masterTemplate->id, $instance->workflow_template_id);
    }
}
