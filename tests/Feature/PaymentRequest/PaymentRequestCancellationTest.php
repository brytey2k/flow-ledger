<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use Tests\TenantAppTestCase;

class PaymentRequestCancellationTest extends TenantAppTestCase
{
    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_only_owner_can_cancel_request(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $otherUser = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $otherStaff = Staff::factory()->withUser($otherUser)->withBranch($this->branch)->create();
        $otherUser->assignRole($this->role);

        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create(['staff_id' => $staff->id, 'status' => 'draft']);

        $response = $this->actingAs($otherUser)->post(
            route('payment-requests.cancel', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('error', __('flash.requests.cancel_not_owner'));

        $paymentRequest->refresh();
        $this->assertSame('draft', $paymentRequest->status);
    }

    public function test_owner_can_cancel_draft_request(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create(['staff_id' => $staff->id, 'status' => 'draft']);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.cancel', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('success', __('flash.requests.cancelled'));

        $paymentRequest->refresh();
        $this->assertSame('cancelled', $paymentRequest->status);
    }

    // ── Status Validation ──────────────────────────────────────────────────────

    public function test_cannot_cancel_disbursed_request(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create([
                'staff_id' => $staff->id,
                'status' => 'disbursed',
                'disbursed_at' => now(),
            ]);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.cancel', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('error', __('flash.requests.cannot_cancel_status'));

        $paymentRequest->refresh();
        $this->assertSame('disbursed', $paymentRequest->status);
    }

    public function test_cannot_cancel_already_cancelled_request(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create([
                'staff_id' => $staff->id,
                'status' => 'cancelled',
            ]);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.cancel', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('error', __('flash.requests.cannot_cancel_status'));

        $paymentRequest->refresh();
        $this->assertSame('cancelled', $paymentRequest->status);
    }

    public function test_owner_can_cancel_in_workflow_request(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create([
                'staff_id' => $staff->id,
                'status' => 'in_workflow',
                'type' => 'advance',
            ]);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.cancel', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('success', __('flash.requests.cancelled'));

        $paymentRequest->refresh();
        $this->assertSame('cancelled', $paymentRequest->status);
    }

    public function test_owner_can_cancel_approved_request(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create([
                'staff_id' => $staff->id,
                'status' => 'approved',
                'approved_at' => now(),
            ]);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.cancel', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('success', __('flash.requests.cancelled'));

        $paymentRequest->refresh();
        $this->assertSame('cancelled', $paymentRequest->status);
    }

    public function test_owner_can_cancel_sent_back_request(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create([
                'staff_id' => $staff->id,
                'status' => 'sent_back',
            ]);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.cancel', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('success', __('flash.requests.cancelled'));

        $paymentRequest->refresh();
        $this->assertSame('cancelled', $paymentRequest->status);
    }

    // ── Branch Scope ───────────────────────────────────────────────────────────

    public function test_user_cannot_cancel_request_from_different_branch(): void
    {
        $paymentRequest = PaymentRequest::factory()
            ->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.cancel', $paymentRequest),
        );

        $response->assertForbidden();
    }

    // ── Cancellation Updates Activity Log ──────────────────────────────────────

    public function test_cancellation_logs_activity(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create(['staff_id' => $staff->id, 'status' => 'draft']);

        $this->actingAs($this->user)->post(route('payment-requests.cancel', $paymentRequest));

        $activity = $paymentRequest->activities()
            ->where('event', 'request.cancelled')
            ->first();

        $this->assertNotNull($activity);
        $this->assertSame('draft', $activity->properties['old_status']);
        $this->assertSame('cancelled', $activity->properties['new_status']);
    }

    // ── Cancellation Stage Tracking ────────────────────────────────────────────

    public function test_cancellation_tracks_stage(): void
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->advance()
            ->create([
                'staff_id' => $staff->id,
                'status' => 'in_workflow',
            ]);

        // Create workflow template and instance
        $template = \App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
        $workflowInstance = \App\Models\Tenant\WorkflowInstance::create([
            'workflow_template_id' => $template->id,
            'workflowable_type' => PaymentRequest::class,
            'workflowable_id' => $paymentRequest->id,
            'status' => 'in_progress',
            'submitter_user_id' => $this->user->id,
            'branch_id' => $this->branch->id,
        ]);

        // Create an active stage
        $stage = \App\Models\Tenant\WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
        $instanceStage = \App\Models\Tenant\WorkflowInstanceStage::create([
            'workflow_instance_id' => $workflowInstance->id,
            'workflow_stage_id' => $stage->id,
            'status' => 'active',
            'started_at' => now(),
        ]);

        $this->actingAs($this->user)->post(route('payment-requests.cancel', $paymentRequest));

        $workflowInstance->refresh();
        $this->assertSame('cancelled', $workflowInstance->status);
        $this->assertSame($instanceStage->id, $workflowInstance->cancelled_at_stage_id);
        $this->assertTrue($workflowInstance->isCancelled());
        $this->assertNotNull($workflowInstance->cancelledAtStage);
    }
}
