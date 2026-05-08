<?php

declare(strict_types=1);

namespace Tests\Feature\Disbursement;

use App\Enums\Tenant\PermissionKey;
use App\Models\Tenant\PaymentRequest;
use Tests\TenantAppTestCase;

class DisbursementsControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('disbursements.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_cannot_disburse(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

        $response = $this->post(route('disbursements.store', $paymentRequest), [
            'disbursement_method' => 'Cash',
        ]);

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DisburseRequests->value);

        $response = $this->actingAs($this->user)->get(route('disbursements.index'));

        $response->assertForbidden();
    }

    public function test_user_without_permission_cannot_disburse(): void
    {
        $this->role->revokePermissionTo(PermissionKey::DisburseRequests->value);
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

        $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
            'disbursement_method' => 'Cash',
        ]);

        $response->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authorised_user_sees_disbursements_index(): void
    {
        PaymentRequest::factory()->advance()->create(['status' => 'approved']);
        PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->get(route('disbursements.index'));

        $response->assertOk();
        $response->assertViewIs('tenant.disbursements.index');
    }

    public function test_index_only_shows_approved_requests(): void
    {
        $approved = PaymentRequest::factory()->advance()->create(['status' => 'approved']);
        PaymentRequest::factory()->advance()->create(['status' => 'draft']);
        PaymentRequest::factory()->advance()->create(['status' => 'disbursed', 'disbursed_at' => now()]);

        $response = $this->actingAs($this->user)->get(route('disbursements.index'));

        $response->assertOk();
        $response->assertViewHas('requests', fn($requests) => $requests->contains($approved));
        $response->assertViewHas('requests', fn($requests) => $requests->total() === 1);
    }

    // ── Disbursement ──────────────────────────────────────────────────────────

    public function test_authorised_user_can_disburse_approved_request(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

        $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
            'disbursement_method' => 'Bank Transfer',
            'disbursement_reference' => 'TXN-001',
        ]);

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_requests', [
            'id' => $paymentRequest->id,
            'status' => 'disbursed',
            'disbursement_method' => 'Bank Transfer',
            'disbursement_reference' => 'TXN-001',
            'disbursed_by_user_id' => $this->user->id,
        ]);
    }

    public function test_disburse_without_reference_is_allowed(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

        $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
            'disbursement_method' => 'Cash',
        ]);

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $this->assertDatabaseHas('payment_requests', [
            'id' => $paymentRequest->id,
            'status' => 'disbursed',
            'disbursement_reference' => null,
        ]);
    }

    public function test_cannot_disburse_non_approved_request(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
            'disbursement_method' => 'Cash',
        ]);

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $response->assertSessionHas('error');

        $this->assertDatabaseHas('payment_requests', ['id' => $paymentRequest->id, 'status' => 'draft']);
    }

    public function test_disbursement_method_is_required(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

        $response = $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
            'disbursement_method' => '',
        ]);

        $response->assertSessionHasErrors('disbursement_method');
        $this->assertDatabaseHas('payment_requests', ['id' => $paymentRequest->id, 'status' => 'approved']);
    }

    public function test_disburse_logs_activity(): void
    {
        $paymentRequest = PaymentRequest::factory()->advance()->create(['status' => 'approved']);

        $this->actingAs($this->user)->post(route('disbursements.store', $paymentRequest), [
            'disbursement_method' => 'Mobile Money',
            'disbursement_reference' => 'MM-999',
        ]);

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => PaymentRequest::class,
            'subject_id' => $paymentRequest->id,
            'event' => 'request.disbursed',
        ]);
    }
}
