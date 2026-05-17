<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use App\Services\NewTenantSetupService;
use App\Services\TenantResetService;
use Illuminate\Support\Facades\Bus;
use Stancl\Tenancy\Jobs\DeleteDatabase;
use Tests\LandlordTestCase;

class TenantsControllerTest extends LandlordTestCase
{
    // ── Authentication ────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_index(): void
    {
        $this->get(route('landlord.tenants.index'))->assertRedirect();
    }

    public function test_guest_is_redirected_from_create(): void
    {
        $this->get(route('landlord.tenants.create'))->assertRedirect();
    }

    // ── Index ─────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_tenants_index(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.tenants.index'))
            ->assertOk()
            ->assertViewHas('tenants');
    }

    // ── Create ────────────────────────────────────────────────────────────────

    public function test_authenticated_user_can_view_create_form(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.tenants.create'))
            ->assertOk();
    }

    // ── Suspend / Unsuspend ───────────────────────────────────────────────────

    public function test_can_suspend_tenant(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.tenants.suspend', $this->tenant))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertTrue($this->tenant->fresh()->isSuspended());
    }

    public function test_can_unsuspend_tenant(): void
    {
        $this->tenant->update(['is_suspended' => true]);

        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.tenants.unsuspend', $this->tenant))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($this->tenant->fresh()->isSuspended());
    }

    // ── Destroy ───────────────────────────────────────────────────────────────

    public function test_destroy_fails_when_name_does_not_match(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->delete(route('landlord.tenants.destroy', $this->tenant), [
                'confirm_tenant_name' => 'WrongName',
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
    }

    public function test_destroy_deletes_tenant_when_name_matches(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->delete(route('landlord.tenants.destroy', $this->tenant), [
                'confirm_tenant_name' => 'Landlord Test Tenant',
                'delete_database' => false,
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseMissing('tenants', ['id' => $this->tenant->id]);
    }

    // ── Store ─────────────────────────────────────────────────────────────────

    public function test_store_creates_tenant_when_valid_data_provided(): void
    {
        $this->mock(NewTenantSetupService::class)
            ->shouldReceive('createTenant')
            ->once();

        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.tenants.store'), [
                'id' => 'new-test-tenant',
                'name' => 'New Test Tenant',
                'admin_email' => 'admin@newtest.com',
                'admin_password' => 'secret123',
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('success');
    }

    // ── Reset ─────────────────────────────────────────────────────────────────

    public function test_reset_fails_when_name_does_not_match(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.tenants.reset', $this->tenant), [
                'confirm_tenant_name' => 'WrongName',
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('error');
    }

    public function test_reset_succeeds_when_name_matches(): void
    {
        $this->mock(TenantResetService::class)
            ->shouldReceive('reset')
            ->once();

        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.tenants.reset', $this->tenant), [
                'confirm_tenant_name' => 'Landlord Test Tenant',
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('success');
    }

    public function test_reset_redirects_with_error_when_service_throws(): void
    {
        $this->mock(TenantResetService::class)
            ->shouldReceive('reset')
            ->once()
            ->andThrow(new \RuntimeException('Reset failed'));

        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.tenants.reset', $this->tenant), [
                'confirm_tenant_name' => 'Landlord Test Tenant',
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('error');
    }

    // ── Destroy with delete_database ─────────────────────────────────────────

    public function test_destroy_with_delete_database_true_dispatches_job(): void
    {
        Bus::fake();

        $this->actingAs($this->landlordUser, 'landlord')
            ->delete(route('landlord.tenants.destroy', $this->tenant), [
                'confirm_tenant_name' => 'Landlord Test Tenant',
                'delete_database' => true,
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('success');

        Bus::assertDispatched(DeleteDatabase::class);
    }
}
