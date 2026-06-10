<?php

declare(strict_types=1);

namespace Tests\Feature\Approvals;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Department;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use Tests\TenantAppTestCase;

class WorkflowApprovalsControllerTest extends TenantAppTestCase
{
    private function submitRequestWithTemplate(): array
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);
        $stage->roles()->attach($this->role->id);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        app(PaymentRequestService::class)->submit($paymentRequest);

        $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();

        return [$paymentRequest, $instanceStage];
    }

    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('approvals.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_show(): void
    {
        [, $instanceStage] = $this->submitRequestWithTemplate();

        $response = $this->get(route('approvals.show', $instanceStage));

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->role->revokePermissionTo('approve requests');

        $response = $this->actingAs($this->user)->get(route('approvals.index'));

        $response->assertForbidden();
    }

    public function test_user_whose_role_is_not_on_stage_cannot_view_review_screen(): void
    {
        [, $instanceStage] = $this->submitRequestWithTemplate();

        // Detach this role from the stage so canUserActOnStage returns false
        $instanceStage->stage->roles()->detach($this->role->id);

        $response = $this->actingAs($this->user)->get(route('approvals.show', $instanceStage));

        $response->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_shows_active_stages_for_user_role(): void
    {
        $this->submitRequestWithTemplate();

        $response = $this->actingAs($this->user)->get(route('approvals.index'));

        $response->assertOk();
        $response->assertViewIs('tenant.approvals.index');
        $response->assertViewHas('instanceStages');
    }

    public function test_index_shows_empty_state_when_nothing_pending(): void
    {
        $response = $this->actingAs($this->user)->get(route('approvals.index'));

        $response->assertOk();
        $response->assertSee('All caught up');
    }

    public function test_index_excludes_branch_scoped_stage_when_user_branch_does_not_match(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
            'scope_to_branch' => true,
        ]);
        $stage->roles()->attach($this->role->id);

        $otherBranch = Branch::factory()->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'draft',
            'branch_id' => $otherBranch->id,
        ]);
        app(PaymentRequestService::class)->submit($paymentRequest);

        // User's staff branch differs from the request's branch
        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $response = $this->actingAs($this->user)->get(route('approvals.index'));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('instanceStages'));
    }

    public function test_index_includes_branch_scoped_stage_when_user_branch_matches(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
            'scope_to_branch' => true,
        ]);
        $stage->roles()->attach($this->role->id);

        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'draft',
            'branch_id' => $this->branch->id,
        ]);
        app(PaymentRequestService::class)->submit($paymentRequest);

        // User's staff branch matches the request's branch
        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $response = $this->actingAs($this->user)->get(route('approvals.index'));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('instanceStages'));
    }

    public function test_index_excludes_department_scoped_stage_when_user_department_does_not_match(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
            'scope_to_department' => true,
        ]);
        $stage->roles()->attach($this->role->id);

        /** @var User $submitter */
        $submitter = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $submitterDept = Department::factory()->create();
        Staff::factory()->withUser($submitter)->create(['department_id' => $submitterDept->id]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        app(PaymentRequestService::class)->submit($paymentRequest, $submitter);

        // User's department differs from the submitter's department
        $otherDept = Department::factory()->create();
        Staff::factory()->withUser($this->user)->create(['department_id' => $otherDept->id]);

        $response = $this->actingAs($this->user)->get(route('approvals.index'));

        $response->assertOk();
        $this->assertCount(0, $response->viewData('instanceStages'));
    }

    public function test_index_includes_department_scoped_stage_when_user_department_matches(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
            'scope_to_department' => true,
        ]);
        $stage->roles()->attach($this->role->id);

        /** @var User $submitter */
        $submitter = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $dept = Department::factory()->create();
        Staff::factory()->withUser($submitter)->create(['department_id' => $dept->id]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        app(PaymentRequestService::class)->submit($paymentRequest, $submitter);

        // User's department matches the submitter's department
        Staff::factory()->withUser($this->user)->create(['department_id' => $dept->id]);

        $response = $this->actingAs($this->user)->get(route('approvals.index'));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('instanceStages'));
    }

    public function test_index_includes_branch_scoped_stage_for_user_without_staff_profile(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
            'scope_to_branch' => true,
        ]);
        $stage->roles()->attach($this->role->id);

        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'draft',
            'branch_id' => $this->branch->id,
        ]);
        app(PaymentRequestService::class)->submit($paymentRequest);

        // User has no staff profile — should bypass branch scope and see all scoped stages
        $response = $this->actingAs($this->user)->get(route('approvals.index'));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('instanceStages'));
    }

    public function test_index_includes_department_scoped_stage_for_user_without_staff_profile(): void
    {
        $template = WorkflowTemplate::factory()->advance()->create();
        $stage = WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
            'scope_to_department' => true,
        ]);
        $stage->roles()->attach($this->role->id);

        /** @var User $submitter */
        $submitter = User::factory()->create([
            'branch_id' => $this->branch->id,
            'operational_branch_id' => $this->branch->id,
        ]);
        $dept = Department::factory()->create();
        Staff::factory()->withUser($submitter)->create(['department_id' => $dept->id]);

        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        app(PaymentRequestService::class)->submit($paymentRequest, $submitter);

        // User has no staff profile — should bypass department scope and see all scoped stages
        $response = $this->actingAs($this->user)->get(route('approvals.index'));

        $response->assertOk();
        $this->assertCount(1, $response->viewData('instanceStages'));
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function test_review_screen_renders_for_eligible_approver(): void
    {
        [, $instanceStage] = $this->submitRequestWithTemplate();

        $response = $this->actingAs($this->user)->get(route('approvals.show', $instanceStage));

        $response->assertOk();
        $response->assertViewIs('tenant.approvals.show');
        $response->assertViewHas('instanceStage');
    }

    // ── Store: Approve ────────────────────────────────────────────────────────

    public function test_approve_marks_stage_approved_and_redirects(): void
    {
        [$paymentRequest, $instanceStage] = $this->submitRequestWithTemplate();

        $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
            'action' => 'approve',
            'comment' => 'Looks good.',
        ]);

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $response->assertSessionHas('success');

        $instanceStage->refresh();
        $this->assertSame('approved', $instanceStage->status);
    }

    public function test_approve_completes_workflow_when_last_stage(): void
    {
        [$paymentRequest, $instanceStage] = $this->submitRequestWithTemplate();

        $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
            'action' => 'approve',
        ]);

        $paymentRequest->refresh();
        $this->assertSame('approved', $paymentRequest->status);
        $this->assertNotNull($paymentRequest->approved_at);
    }

    // ── Store: Reject ─────────────────────────────────────────────────────────

    public function test_reject_cancels_workflow_and_redirects(): void
    {
        [$paymentRequest, $instanceStage] = $this->submitRequestWithTemplate();

        $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
            'action' => 'reject',
            'comment' => 'Not justified.',
        ]);

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));

        $paymentRequest->refresh();
        $this->assertSame('cancelled', $paymentRequest->status);
    }

    public function test_reject_requires_comment(): void
    {
        [, $instanceStage] = $this->submitRequestWithTemplate();

        $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
            'action' => 'reject',
        ]);

        $response->assertSessionHasErrors(['comment']);
    }

    // ── Store: Send Back ──────────────────────────────────────────────────────

    public function test_send_back_sets_request_to_sent_back_status(): void
    {
        [$paymentRequest, $instanceStage] = $this->submitRequestWithTemplate();

        $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
            'action' => 'send_back',
            'comment' => 'Please revise the amounts.',
        ]);

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));

        $paymentRequest->refresh();
        $this->assertSame('sent_back', $paymentRequest->status);

        $instanceStage->refresh();
        $this->assertSame('sent_back', $instanceStage->status);
    }

    public function test_send_back_requires_comment(): void
    {
        [, $instanceStage] = $this->submitRequestWithTemplate();

        $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
            'action' => 'send_back',
        ]);

        $response->assertSessionHasErrors(['comment']);
    }

    // ── Denormalized Context ──────────────────────────────────────────────────

    public function test_start_workflow_persists_branch_and_department_on_instance(): void
    {
        $dept = Department::factory()->create();
        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create(['department_id' => $dept->id]);

        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'draft',
            'branch_id' => $this->branch->id,
        ]);

        $template = WorkflowTemplate::factory()->advance()->create();
        WorkflowStage::factory()->create([
            'workflow_template_id' => $template->id,
            'display_order' => 1,
        ]);

        app(PaymentRequestService::class)->submit($paymentRequest, $this->user);

        $instance = $paymentRequest->workflowInstances()->first();
        $this->assertSame($this->branch->id, $instance->branch_id);
        $this->assertSame($dept->id, $instance->department_id);
    }

    // ── Store: Validation ─────────────────────────────────────────────────────

    public function test_store_requires_valid_action(): void
    {
        [, $instanceStage] = $this->submitRequestWithTemplate();

        $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
            'action' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['action']);
    }

    public function test_user_whose_role_is_not_on_stage_cannot_act(): void
    {
        [, $instanceStage] = $this->submitRequestWithTemplate();
        $instanceStage->stage->roles()->detach($this->role->id);

        $response = $this->actingAs($this->user)->post(route('approvals.store', $instanceStage), [
            'action' => 'approve',
        ]);

        $response->assertForbidden();
    }
}
