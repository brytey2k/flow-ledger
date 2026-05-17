<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\RetirementRequest;
use Tests\TenantAppTestCase;

class RetirementRequestModelTest extends TenantAppTestCase
{
    public function test_is_draft_returns_true_when_status_is_draft(): void
    {
        $retirement = RetirementRequest::factory()->create(['status' => 'draft']);

        $this->assertTrue($retirement->isDraft());
    }

    public function test_is_draft_returns_false_when_status_is_not_draft(): void
    {
        $retirement = RetirementRequest::factory()->create(['status' => 'in_workflow']);

        $this->assertFalse($retirement->isDraft());
    }

    public function test_is_sent_back_returns_true_when_status_is_sent_back(): void
    {
        $retirement = RetirementRequest::factory()->create(['status' => 'sent_back']);

        $this->assertTrue($retirement->isSentBack());
    }

    public function test_is_sent_back_returns_false_for_other_statuses(): void
    {
        $retirement = RetirementRequest::factory()->create(['status' => 'draft']);

        $this->assertFalse($retirement->isSentBack());
    }

    public function test_get_type_attribute_returns_retirement(): void
    {
        $retirement = RetirementRequest::factory()->create();

        $this->assertSame('retirement', $retirement->getTypeAttribute());
    }

    public function test_payment_request_relation_loads_correctly(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);
        $retirement = RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id]);

        $this->assertEquals($paymentRequest->id, $retirement->paymentRequest->id);
    }

    public function test_settled_by_relation_loads_user(): void
    {
        $user = \App\Models\Tenant\User::factory()->create();
        $retirement = RetirementRequest::factory()->create(['settled_by_user_id' => $user->id]);

        $this->assertEquals($user->id, $retirement->settledBy?->id);
    }

    public function test_staff_relation_resolves_through_payment_request(): void
    {
        $staff = \App\Models\Tenant\Staff::factory()->create();
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
            'staff_id' => $staff->id,
        ]);
        $retirement = RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id]);

        $this->assertEquals($staff->id, $retirement->staff->id);
    }

    public function test_branch_relation_resolves_through_payment_request(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);
        $retirement = RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id]);

        $this->assertNotNull($retirement->branch);
    }

    public function test_currency_relation_resolves_through_payment_request(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create([
            'status' => 'disbursed',
            'disbursed_at' => now(),
        ]);
        $retirement = RetirementRequest::factory()->create(['payment_request_id' => $paymentRequest->id]);

        $this->assertNotNull($retirement->currency);
    }

    public function test_items_relation_returns_has_many(): void
    {
        $retirement = RetirementRequest::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $retirement->items);
    }

    public function test_workflow_instances_relation_returns_morph_many(): void
    {
        $retirement = RetirementRequest::factory()->create();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $retirement->workflowInstances);
    }
}
