<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Models\Tenant\PaymentRequest;
use Tests\TenantAppTestCase;

class PaymentRequestDeclineTest extends TenantAppTestCase
{
    public function test_only_approver_can_decline_request(): void
    {
        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create(['status' => 'in_workflow']);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.decline', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('error', __('flash.requests.no_active_workflow'));

        $paymentRequest->refresh();
        $this->assertSame('in_workflow', $paymentRequest->status);
    }

    public function test_cannot_decline_draft_request(): void
    {
        $paymentRequest = PaymentRequest::factory()
            ->for($this->branch)
            ->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.decline', $paymentRequest),
        );

        $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
        $response->assertSessionHas('error', __('flash.requests.no_active_workflow'));

        $paymentRequest->refresh();
        $this->assertSame('draft', $paymentRequest->status);
    }

    public function test_user_cannot_decline_request_from_different_branch(): void
    {
        $paymentRequest = PaymentRequest::factory()
            ->create(['status' => 'in_workflow']);

        $response = $this->actingAs($this->user)->post(
            route('payment-requests.decline', $paymentRequest),
        );

        $response->assertForbidden();
    }

    public function test_decline_rejects_all_statuses(): void
    {
        foreach (['draft', 'approved', 'sent_back', 'disbursed', 'cancelled'] as $status) {
            $paymentRequest = PaymentRequest::factory()
                ->for($this->branch)
                ->create(['status' => $status]);

            $response = $this->actingAs($this->user)->post(
                route('payment-requests.decline', $paymentRequest),
            );

            $response->assertRedirectToRoute('payment-requests.show', $paymentRequest);
            $response->assertSessionHas('error');

            $paymentRequest->refresh();
            $this->assertNotSame('denied', $paymentRequest->status);
        }
    }
}
