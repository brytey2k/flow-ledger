<?php

declare(strict_types=1);

namespace Tests\Feature\Approvals;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use App\Services\WorkflowEngineService;
use Tests\TenantAppTestCase;

class PaymentRequestResubmitControllerTest extends TenantAppTestCase
{
    private function buildSentBackRequest(): PaymentRequest
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        $stage->roles()->attach($this->role->id);

        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'draft',
            'staff_id' => $staff->id,
        ]);
        app(PaymentRequestService::class)->submit($paymentRequest);

        $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();
        app(WorkflowEngineService::class)->sendBack($instanceStage, $this->user, 'Please fix amounts.');

        $paymentRequest->refresh();

        return $paymentRequest;
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected(): void
    {
        $paymentRequest = PaymentRequest::factory()->create(['status' => 'sent_back']);

        $response = $this->post(route('payment-requests.resubmit', $paymentRequest));

        $response->assertRedirect(route('login'));
    }

    // ── Happy Path ────────────────────────────────────────────────────────────

    public function test_resubmit_reactivates_stage_and_sets_in_workflow(): void
    {
        $paymentRequest = $this->buildSentBackRequest();

        $response = $this->actingAs($this->user)
            ->post(route('payment-requests.resubmit', $paymentRequest));

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $response->assertSessionHas('success');

        $paymentRequest->refresh();
        $this->assertSame('in_workflow', $paymentRequest->status);
    }

    public function test_resubmit_clears_sent_back_stage_on_instance(): void
    {
        $paymentRequest = $this->buildSentBackRequest();

        $this->actingAs($this->user)
            ->post(route('payment-requests.resubmit', $paymentRequest));

        $instance = $paymentRequest->activeWorkflowInstance()->first();
        $this->assertNull($instance?->sent_back_to_stage_id);
    }

    // ── Failure Paths ─────────────────────────────────────────────────────────

    public function test_cannot_resubmit_non_sent_back_request(): void
    {
        $paymentRequest = PaymentRequest::factory()->inWorkflow()->create();

        $response = $this->actingAs($this->user)
            ->post(route('payment-requests.resubmit', $paymentRequest));

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $response->assertSessionHas('error');
    }

    public function test_non_owner_cannot_resubmit(): void
    {
        $paymentRequest = $this->buildSentBackRequest();

        $otherUser = User::factory()->create();
        $otherUser->assignRole($this->role);

        $response = $this->actingAs($otherUser)
            ->post(route('payment-requests.resubmit', $paymentRequest));

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $response->assertSessionHas('error');

        $paymentRequest->refresh();
        $this->assertSame('sent_back', $paymentRequest->status);
    }
}
