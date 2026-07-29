<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;
use App\Models\Tenant\WorkflowInstanceStage;
use App\Models\Tenant\WorkflowStage;
use App\Models\Tenant\WorkflowTemplate;
use App\Services\PaymentRequestService;
use App\Services\WorkflowEngineService;

function buildSentBackRequest(): PaymentRequest
{
    $template = WorkflowTemplate::factory()->advance()->create();
    $stage = WorkflowStage::factory()->create([
        'workflow_template_id' => $template->id,
        'display_order' => 1,
    ]);
    $stage->roles()->attach(test()->role->id);

    $staff = Staff::factory()->withUser(test()->user)->withBranch(test()->branch)->create();
    $paymentRequest = PaymentRequest::factory()->advance()->create([
        'status' => 'draft',
        'staff_id' => $staff->id,
    ]);
    app(PaymentRequestService::class)->submit($paymentRequest);

    $instanceStage = WorkflowInstanceStage::where('status', 'active')->latest()->first();
    app(WorkflowEngineService::class)->sendBack($instanceStage, test()->user, 'Please fix amounts.');

    $paymentRequest->refresh();

    return $paymentRequest;
}
test('guest is redirected', function () {
    $paymentRequest = PaymentRequest::factory()->create(['status' => 'sent_back']);

    $response = $this->post(route('payment-requests.resubmit', $paymentRequest));

    $response->assertRedirect(route('login'));
});
test('resubmit reactivates stage and sets in workflow', function () {
    $paymentRequest = buildSentBackRequest();

    $response = $this->actingAs($this->user)
        ->post(route('payment-requests.resubmit', $paymentRequest));

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('success');

    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBe('in_workflow');
});
test('resubmit clears sent back stage on instance', function () {
    $paymentRequest = buildSentBackRequest();

    $this->actingAs($this->user)
        ->post(route('payment-requests.resubmit', $paymentRequest));

    $instance = $paymentRequest->activeWorkflowInstance()->first();
    expect($instance?->sent_back_to_stage_id)->toBeNull();
});
test('cannot resubmit non sent back request', function () {
    $paymentRequest = PaymentRequest::factory()->inWorkflow()->create();

    $response = $this->actingAs($this->user)
        ->post(route('payment-requests.resubmit', $paymentRequest));

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('error');
});
test('non owner cannot resubmit', function () {
    $paymentRequest = buildSentBackRequest();

    $otherUser = User::factory()->create();
    $otherUser->assignRole($this->role);

    $response = $this->actingAs($otherUser)
        ->post(route('payment-requests.resubmit', $paymentRequest));

    $response->assertRedirect(route('payment-requests.show', $paymentRequest));
    $response->assertSessionHas('error');

    $paymentRequest->refresh();
    expect($paymentRequest->status)->toBe('sent_back');
});
