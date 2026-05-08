<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowTemplate;
use Tests\TenantAppTestCase;

class PaymentRequestSubmitControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_submit(): void
    {
        $request = PaymentRequest::factory()->create();

        $response = $this->post(route('payment-requests.submit', $request));

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_submit(): void
    {
        $request = PaymentRequest::factory()->create();
        $this->role->revokePermissionTo('create payment request');

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

        $response->assertForbidden();
    }

    // ── Happy Path ────────────────────────────────────────────────────────────

    public function test_submit_starts_workflow_and_redirects(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        WorkflowTemplate::factory()->advance()->create();

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

        $response->assertRedirect(route('payment-requests.show', $request));
        $response->assertSessionHas('success');

        $request->refresh();
        $this->assertSame('in_workflow', $request->status);
        $this->assertNotNull($request->submitted_at);
        $this->assertInstanceOf(WorkflowInstance::class, $request->activeWorkflowInstance);
    }

    // ── Failure Paths ─────────────────────────────────────────────────────────

    public function test_cannot_submit_non_draft_request(): void
    {
        $request = PaymentRequest::factory()->inWorkflow()->create();

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

        $response->assertRedirect(route('payment-requests.show', $request));
        $response->assertSessionHas('error');
    }

    public function test_cannot_submit_when_no_workflow_template_exists(): void
    {
        WorkflowTemplate::query()->delete();
        $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

        $response->assertRedirect(route('payment-requests.show', $request));
        $response->assertSessionHas('error');

        $request->refresh();
        $this->assertSame('draft', $request->status);
    }
}
