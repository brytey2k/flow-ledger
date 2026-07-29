<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\Staff;
use App\Models\Tenant\User;

test('only owner can cancel request', function () {
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
    expect($paymentRequest->status)->toBe('draft');
});
test('owner can cancel draft request', function () {
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
    expect($paymentRequest->status)->toBe('cancelled');
});
test('cannot cancel disbursed request', function () {
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
    expect($paymentRequest->status)->toBe('disbursed');
});
test('cannot cancel already cancelled request', function () {
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
    expect($paymentRequest->status)->toBe('cancelled');
});
test('owner can cancel in workflow request', function () {
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
    expect($paymentRequest->status)->toBe('cancelled');
});
test('owner can cancel approved request', function () {
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
    expect($paymentRequest->status)->toBe('cancelled');
});
test('owner can cancel sent back request', function () {
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
    expect($paymentRequest->status)->toBe('cancelled');
});
test('user cannot cancel request from different branch', function () {
    $paymentRequest = PaymentRequest::factory()
        ->create(['status' => 'draft']);

    $response = $this->actingAs($this->user)->post(
        route('payment-requests.cancel', $paymentRequest),
    );

    $response->assertForbidden();
});
test('cancellation logs activity', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

    $paymentRequest = PaymentRequest::factory()
        ->for($this->branch)
        ->create(['staff_id' => $staff->id, 'status' => 'draft']);

    $this->actingAs($this->user)->post(route('payment-requests.cancel', $paymentRequest));

    $activity = $paymentRequest->activities()
        ->where('event', 'request.cancelled')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->properties['old_status'])->toBe('draft');
    expect($activity->properties['new_status'])->toBe('cancelled');
});
test('cancellation tracks stage', function () {
    $staff = Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

    $paymentRequest = PaymentRequest::factory()
        ->for($this->branch)
        ->advance()
        ->create([
            'staff_id' => $staff->id,
            'status' => 'in_workflow',
        ]);

    // Create workflow template and instance
    $template = App\Models\Tenant\WorkflowTemplate::factory()->create(['type' => 'advance']);
    $workflowInstance = App\Models\Tenant\WorkflowInstance::create([
        'workflow_template_id' => $template->id,
        'workflowable_type' => PaymentRequest::class,
        'workflowable_id' => $paymentRequest->id,
        'status' => 'in_progress',
        'submitter_user_id' => $this->user->id,
        'branch_id' => $this->branch->id,
    ]);

    // Create an active stage
    $stage = App\Models\Tenant\WorkflowStage::factory()->create(['workflow_template_id' => $template->id]);
    $instanceStage = App\Models\Tenant\WorkflowInstanceStage::create([
        'workflow_instance_id' => $workflowInstance->id,
        'workflow_stage_id' => $stage->id,
        'status' => 'active',
        'started_at' => now(),
    ]);

    $this->actingAs($this->user)->post(route('payment-requests.cancel', $paymentRequest));

    $workflowInstance->refresh();
    expect($workflowInstance->status)->toBe('cancelled');
    expect($workflowInstance->cancelled_at_stage_id)->toBe($instanceStage->id);
    expect($workflowInstance->isCancelled())->toBeTrue();
    expect($workflowInstance->cancelledAtStage)->not->toBeNull();
});
