<?php

declare(strict_types=1);

namespace Tests\Feature\Landlord;

use App\Models\Tenant;
use App\Models\Tenant\User;
use App\Services\IdpTenantService;
use App\Services\NewTenantSetupService;
use App\Services\TenantImpersonationService;
use App\Services\TenantResetService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use RuntimeException;
use Stancl\Tenancy\Database\Models\ImpersonationToken;
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
        $this->mock(IdpTenantService::class)
            ->shouldReceive('listTenants')
            ->once()
            ->andReturn([['id' => 1, 'name' => 'Idp Tenant', 'slug' => 'idp-tenant']]);

        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.tenants.create'))
            ->assertOk()
            ->assertViewHas('idpTenants', [['id' => 1, 'name' => 'Idp Tenant', 'slug' => 'idp-tenant']]);
    }

    public function test_create_form_degrades_to_empty_list_when_idp_call_fails(): void
    {
        $this->mock(IdpTenantService::class)
            ->shouldReceive('listTenants')
            ->once()
            ->andThrow(new RuntimeException('IDP unreachable'));

        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.tenants.create'))
            ->assertOk()
            ->assertViewHas('idpTenants', []);
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
            ->once()
            ->with('new-test-tenant', 'New Test Tenant', 'admin@newtest.com', 'secret123', null);

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

    public function test_store_passes_idp_tenant_id_through_to_service(): void
    {
        $this->mock(NewTenantSetupService::class)
            ->shouldReceive('createTenant')
            ->once()
            ->with('new-test-tenant', 'New Test Tenant', 'admin@newtest.com', 'secret123', '42');

        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.tenants.store'), [
                'id' => 'new-test-tenant',
                'name' => 'New Test Tenant',
                'admin_email' => 'admin@newtest.com',
                'admin_password' => 'secret123',
                'idp_tenant_id' => '42',
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('success');
    }

    public function test_store_rejects_duplicate_idp_tenant_id(): void
    {
        $this->tenant->update(['idp_tenant_id' => '99']);

        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.tenants.store'), [
                'id' => 'new-test-tenant',
                'name' => 'New Test Tenant',
                'admin_email' => 'admin@newtest.com',
                'admin_password' => 'secret123',
                'idp_tenant_id' => '99',
            ])
            ->assertSessionHasErrors('idp_tenant_id');
    }

    // ── Edit / Update ─────────────────────────────────────────────────────────

    public function test_guest_is_redirected_from_edit(): void
    {
        $this->get(route('landlord.tenants.edit', $this->tenant))->assertRedirect();
    }

    public function test_authenticated_user_can_view_edit_form(): void
    {
        $this->mock(IdpTenantService::class)
            ->shouldReceive('listTenants')
            ->once()
            ->andReturn([['id' => 1, 'name' => 'Idp Tenant', 'slug' => 'idp-tenant']]);

        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.tenants.edit', $this->tenant))
            ->assertOk()
            ->assertViewHas('tenant', fn($tenant) => $tenant->is($this->tenant))
            ->assertViewHas('idpTenants', [['id' => 1, 'name' => 'Idp Tenant', 'slug' => 'idp-tenant']]);
    }

    public function test_edit_form_degrades_to_empty_list_when_idp_call_fails(): void
    {
        $this->mock(IdpTenantService::class)
            ->shouldReceive('listTenants')
            ->once()
            ->andThrow(new RuntimeException('IDP unreachable'));

        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.tenants.edit', $this->tenant))
            ->assertOk()
            ->assertViewHas('idpTenants', []);
    }

    public function test_update_saves_tenant_details(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->put(route('landlord.tenants.update', $this->tenant), [
                'name' => 'Renamed Tenant',
                'idp_tenant_id' => '7',
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('success');

        $this->tenant->refresh();
        $this->assertSame('Renamed Tenant', $this->tenant->name);
        $this->assertSame('7', $this->tenant->idp_tenant_id);
    }

    public function test_update_allows_clearing_idp_tenant_id(): void
    {
        $this->tenant->update(['idp_tenant_id' => '7']);

        $this->actingAs($this->landlordUser, 'landlord')
            ->put(route('landlord.tenants.update', $this->tenant), [
                'name' => 'Renamed Tenant',
            ])
            ->assertRedirect(route('landlord.tenants.index'))
            ->assertSessionHas('success');

        $this->assertNull($this->tenant->refresh()->idp_tenant_id);
    }

    public function test_update_requires_name(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->put(route('landlord.tenants.update', $this->tenant), [
                'name' => '',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_update_rejects_idp_tenant_id_already_used_by_another_tenant(): void
    {
        $otherTenant = new Tenant(['id' => 'other-tenant', 'name' => 'Other Tenant', 'idp_tenant_id' => '55']);
        $otherTenant->saveQuietly();

        $this->actingAs($this->landlordUser, 'landlord')
            ->put(route('landlord.tenants.update', $this->tenant), [
                'name' => 'Renamed Tenant',
                'idp_tenant_id' => '55',
            ])
            ->assertSessionHasErrors('idp_tenant_id');
    }

    public function test_update_allows_keeping_own_idp_tenant_id(): void
    {
        $this->tenant->update(['idp_tenant_id' => '7']);

        $this->actingAs($this->landlordUser, 'landlord')
            ->put(route('landlord.tenants.update', $this->tenant), [
                'name' => 'Renamed Tenant',
                'idp_tenant_id' => '7',
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
            ->andThrow(new RuntimeException('Reset failed'));

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

    // ── Impersonation — select-user ───────────────────────────────────────────

    public function test_guest_cannot_view_select_user_page(): void
    {
        $this->get(route('landlord.tenants.select-user', $this->tenant))->assertRedirect();
    }

    public function test_authenticated_landlord_can_view_select_user_page(): void
    {
        $this->mock(TenantImpersonationService::class)
            ->shouldReceive('getTenantUsersPaginated')
            ->once()
            ->andReturn(new LengthAwarePaginator([], 0, 15, 1));

        $this->actingAs($this->landlordUser, 'landlord')
            ->get(route('landlord.tenants.select-user', $this->tenant))
            ->assertOk()
            ->assertViewIs('landlord.tenants.select-user')
            ->assertViewHas('users');
    }

    // ── Impersonation — impersonate ───────────────────────────────────────────

    public function test_guest_cannot_post_impersonate(): void
    {
        $this->post(route('landlord.impersonate', $this->tenant), [
            'user_identifier' => 'admin@example.com',
        ])->assertRedirect();
    }

    public function test_impersonate_requires_user_identifier(): void
    {
        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.impersonate', $this->tenant), [])
            ->assertSessionHasErrors('user_identifier');
    }

    public function test_impersonate_returns_error_when_user_not_found(): void
    {
        $this->mock(TenantImpersonationService::class)
            ->shouldReceive('findTenantUser')
            ->once()
            ->andReturn(null);

        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.impersonate', $this->tenant), [
                'user_identifier' => 'notfound@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_impersonate_returns_error_when_tenant_has_no_domain(): void
    {
        $this->mock(TenantImpersonationService::class)
            ->shouldReceive('findTenantUser')
            ->once()
            ->andReturn(new User());

        $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.impersonate', $this->tenant), [
                'user_identifier' => 'admin@example.com',
            ])
            ->assertRedirect()
            ->assertSessionHas('error');
    }

    public function test_impersonate_redirects_to_tenant_domain_url_with_token(): void
    {
        $domain = $this->tenant->domains()->create(['domain' => 'test-' . Str::random(6) . '.localhost']);

        $fakeToken = new ImpersonationToken();
        $fakeToken->token = Str::random(128);

        $mock = $this->mock(TenantImpersonationService::class);
        $mock->shouldReceive('findTenantUser')->once()->andReturn(new User());
        $mock->shouldReceive('createImpersonationToken')->once()->andReturn($fakeToken);

        $response = $this->actingAs($this->landlordUser, 'landlord')
            ->post(route('landlord.impersonate', $this->tenant), [
                'user_identifier' => 'admin@example.com',
            ]);

        $response->assertRedirect();
        $this->assertStringContainsString($domain->domain . '/impersonate/' . $fakeToken->token, $response->headers->get('Location'));
    }
}
