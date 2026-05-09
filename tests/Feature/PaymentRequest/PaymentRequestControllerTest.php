<?php

declare(strict_types=1);

namespace Tests\Feature\PaymentRequest;

use App\Models\Tenant\Branch;
use App\Models\Tenant\Currency;
use App\Models\Tenant\PaymentRequest;
use App\Models\Tenant\PaymentRequestItem;
use App\Models\Tenant\Staff;
use Tests\TenantAppTestCase;

class PaymentRequestControllerTest extends TenantAppTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $response = $this->get(route('payment-requests.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $response = $this->get(route('payment-requests.create'));

        $response->assertRedirect(route('login'));
    }

    // ── Authorization ─────────────────────────────────────────────────────────

    public function test_user_without_permission_cannot_access_index(): void
    {
        $this->role->revokePermissionTo('access payment requests');

        $response = $this->actingAs($this->user)->get(route('payment-requests.index'));

        $response->assertForbidden();
    }

    public function test_user_without_permission_cannot_access_create_form(): void
    {
        $this->role->revokePermissionTo('create payment request');

        $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

        $response->assertForbidden();
    }

    public function test_user_without_permission_cannot_access_show(): void
    {
        $request = PaymentRequest::factory()->create();
        $this->role->revokePermissionTo('access payment requests');

        $response = $this->actingAs($this->user)->get(route('payment-requests.show', $request));

        $response->assertForbidden();
    }

    public function test_user_without_delete_permission_cannot_delete(): void
    {
        $request = PaymentRequest::factory()->create();
        $this->role->revokePermissionTo('delete payment request');

        $response = $this->actingAs($this->user)->delete(route('payment-requests.destroy', $request));

        $response->assertForbidden();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_index_renders_with_requests(): void
    {
        PaymentRequest::factory()->count(3)->create();

        $response = $this->actingAs($this->user)->get(route('payment-requests.index'));

        $response->assertOk();
        $response->assertViewIs('tenant.payment-requests.index');
        $response->assertViewHas('requests');
    }

    public function test_index_shows_empty_state_when_no_requests(): void
    {
        PaymentRequest::query()->delete();

        $response = $this->actingAs($this->user)->get(route('payment-requests.index'));

        $response->assertOk();
        $response->assertSee('No requests yet');
    }

    // ── Create Form ───────────────────────────────────────────────────────────

    public function test_create_form_renders(): void
    {
        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

        $response->assertOk();
        $response->assertViewIs('tenant.payment-requests.create');
        $response->assertViewHas(['staffProfile', 'currencies']);
    }

    public function test_create_form_redirects_if_no_staff_profile(): void
    {
        $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

        $response->assertRedirect(route('payment-requests.index'));
        $response->assertSessionHas('error');
    }

    public function test_create_form_redirects_if_staff_has_no_branch(): void
    {
        Staff::factory()->withUser($this->user)->create(); // no branch

        $response = $this->actingAs($this->user)->get(route('payment-requests.create'));

        $response->assertRedirect(route('payment-requests.index'));
        $response->assertSessionHas('error');
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_draft_and_redirects_to_show(): void
    {
        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
            'type' => 'advance',
            'currency_id' => $currency->id,
            'notes' => 'Test notes',
            'items' => [
                ['description' => 'Transport', 'amount' => '150.00'],
                ['description' => 'Accommodation', 'amount' => '300.00'],
            ],
        ]);

        $paymentRequest = PaymentRequest::latest()->first();
        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('payment_requests', [
            'type' => 'advance',
            'status' => 'draft',
            'total_amount' => '450.00',
        ]);

        $this->assertDatabaseHas('payment_request_items', ['description' => 'Transport', 'amount' => '150.00']);
        $this->assertDatabaseHas('payment_request_items', ['description' => 'Accommodation', 'amount' => '300.00']);
    }

    public function test_store_is_forbidden_when_user_has_no_staff_profile(): void
    {
        $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
            'type' => 'advance',
            'currency_id' => 1,
            'items' => [['description' => 'Test', 'amount' => '100']],
        ]);

        $response->assertForbidden();
    }

    public function test_store_fails_validation_without_required_fields(): void
    {
        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();

        $response = $this->actingAs($this->user)->post(route('payment-requests.store'), []);

        $response->assertSessionHasErrors(['type', 'currency_id', 'items']);
    }

    public function test_store_fails_validation_with_invalid_type(): void
    {
        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
            'type' => 'invalid_type',
            'currency_id' => $currency->id,
            'items' => [['description' => 'Test', 'amount' => '100']],
        ]);

        $response->assertSessionHasErrors(['type']);
    }

    public function test_store_fails_validation_with_empty_items_array(): void
    {
        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
            'type' => 'advance',
            'currency_id' => $currency->id,
            'items' => [],
        ]);

        $response->assertSessionHasErrors(['items']);
    }

    public function test_store_fails_validation_when_item_amount_is_zero(): void
    {
        Staff::factory()->withUser($this->user)->withBranch($this->branch)->create();
        $currency = Currency::factory()->create();

        $response = $this->actingAs($this->user)->post(route('payment-requests.store'), [
            'type' => 'advance',
            'currency_id' => $currency->id,
            'items' => [['description' => 'Test', 'amount' => '0']],
        ]);

        $response->assertSessionHasErrors(['items.0.amount']);
    }

    // ── Show ─────────────────────────────────────────────────────────────────

    public function test_show_renders_payment_request(): void
    {
        $paymentRequest = PaymentRequest::factory()->create();
        PaymentRequestItem::factory()->create(['payment_request_id' => $paymentRequest->id]);

        $response = $this->actingAs($this->user)->get(route('payment-requests.show', $paymentRequest));

        $response->assertOk();
        $response->assertViewIs('tenant.payment-requests.show');
        $response->assertViewHas('paymentRequest');
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_deletes_draft_and_redirects_to_index(): void
    {
        $paymentRequest = PaymentRequest::factory()->create(['status' => 'draft']);

        $response = $this->actingAs($this->user)->delete(route('payment-requests.destroy', $paymentRequest));

        $response->assertRedirect(route('payment-requests.index'));
        $response->assertSessionHas('success');
        $this->assertSoftDeleted('payment_requests', ['id' => $paymentRequest->id]);
    }

    public function test_destroy_refuses_non_draft(): void
    {
        $paymentRequest = PaymentRequest::factory()->inWorkflow()->create();

        $response = $this->actingAs($this->user)->delete(route('payment-requests.destroy', $paymentRequest));

        $response->assertRedirect(route('payment-requests.show', $paymentRequest));
        $response->assertSessionHas('error');
        $this->assertModelExists($paymentRequest);
    }
}
