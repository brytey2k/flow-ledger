<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\WorkflowInstance;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use Tests\TenantAppTestCase;

class PaymentRequestSubmitControllerTest extends TenantAppTestCase
{
    private function ownedDraftRequest(array $overrides = []): PaymentRequest
    {
        $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        return PaymentRequest::factory()->advance()->create(array_merge([
            'status' => 'draft',
            'branch_id' => $this->branch->id,
            'staff_id' => $staff->id,
        ], $overrides));
    }

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

    public function test_user_cannot_submit_request_from_different_branch(): void
    {
        $request = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

        $response->assertForbidden();
    }

    public function test_user_cannot_submit_request_they_do_not_own(): void
    {
        $otherStaff = Staff::factory()->withBranch($this->branch)->create();
        $request = PaymentRequest::factory()->advance()->create([
            'status' => 'draft',
            'branch_id' => $this->branch->id,
            'staff_id' => $otherStaff->id,
        ]);

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

        $response->assertRedirect(route('payment-requests.show', $request));
        $response->assertSessionHas('error');
        $this->assertSame('draft', $request->fresh()->status);
    }

    // ── Happy Path ────────────────────────────────────────────────────────────

    public function test_submit_starts_workflow_and_redirects(): void
    {
        $request = $this->ownedDraftRequest();
        $template = WorkflowTemplate::factory()->advance()->create();
        WorkflowStage::factory()->for($template, 'template')->create();

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
        $request = $this->ownedDraftRequest(['status' => 'in_workflow']);

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

        $response->assertRedirect(route('payment-requests.show', $request));
        $response->assertSessionHas('error');
    }

    public function test_cannot_submit_when_no_workflow_template_exists(): void
    {
        WorkflowTemplate::query()->delete();
        $request = $this->ownedDraftRequest();

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

        $response->assertRedirect(route('payment-requests.show', $request));
        $response->assertSessionHas('error');

        $request->refresh();
        $this->assertSame('draft', $request->status);
    }

    public function test_cannot_submit_when_workflow_template_has_no_stages(): void
    {
        $request = $this->ownedDraftRequest();
        WorkflowTemplate::factory()->advance()->create();

        $response = $this->actingAs($this->user)->post(route('payment-requests.submit', $request));

        $response->assertRedirect(route('payment-requests.show', $request));
        $response->assertSessionHas('error');

        $request->refresh();
        $this->assertSame('draft', $request->status);
    }
}
