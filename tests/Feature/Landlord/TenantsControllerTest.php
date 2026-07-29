<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Models\Tenant;
use App\Models\Tenant\User;
use App\Services\IdpTenantService;
use App\Services\NewTenantSetupService;
use App\Services\TenantImpersonationService;
use App\Services\TenantResetService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Str;
use Stancl\Tenancy\Database\Models\ImpersonationToken;
use Stancl\Tenancy\Jobs\DeleteDatabase;

test('guest is redirected from index', function () {
    $this->get(route('landlord.tenants.index'))->assertRedirect();
});
test('guest is redirected from create', function () {
    $this->get(route('landlord.tenants.create'))->assertRedirect();
});
test('authenticated user can view tenants index', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.tenants.index'))
        ->assertOk()
        ->assertViewHas('tenants');
});
test('authenticated user can view create form', function () {
    $this->mock(IdpTenantService::class)
        ->shouldReceive('listTenants')
        ->once()
        ->andReturn([['id' => 1, 'name' => 'Idp Tenant', 'slug' => 'idp-tenant']]);

    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.tenants.create'))
        ->assertOk()
        ->assertViewHas('idpTenants', [['id' => 1, 'name' => 'Idp Tenant', 'slug' => 'idp-tenant']]);
});
test('create form degrades to empty list when idp call fails', function () {
    $this->mock(IdpTenantService::class)
        ->shouldReceive('listTenants')
        ->once()
        ->andThrow(new RuntimeException('IDP unreachable'));

    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.tenants.create'))
        ->assertOk()
        ->assertViewHas('idpTenants', []);
});
test('can suspend tenant', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.tenants.suspend', $this->tenant))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->tenant->fresh()->isSuspended())->toBeTrue();
});
test('can unsuspend tenant', function () {
    $this->tenant->update(['is_suspended' => true]);

    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.tenants.unsuspend', $this->tenant))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($this->tenant->fresh()->isSuspended())->toBeFalse();
});
test('destroy fails when name does not match', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->delete(route('landlord.tenants.destroy', $this->tenant), [
            'confirm_tenant_name' => 'WrongName',
        ])
        ->assertRedirect(route('landlord.tenants.index'))
        ->assertSessionHas('error');

    $this->assertDatabaseHas('tenants', ['id' => $this->tenant->id]);
});
test('destroy deletes tenant when name matches', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->delete(route('landlord.tenants.destroy', $this->tenant), [
            'confirm_tenant_name' => 'Landlord Test Tenant',
            'delete_database' => false,
        ])
        ->assertRedirect(route('landlord.tenants.index'))
        ->assertSessionHas('success');

    $this->assertDatabaseMissing('tenants', ['id' => $this->tenant->id]);
});
test('store creates tenant when valid data provided', function () {
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
});
test('store passes idp tenant id through to service', function () {
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
});
test('store rejects duplicate idp tenant id', function () {
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
});
test('guest is redirected from edit', function () {
    $this->get(route('landlord.tenants.edit', $this->tenant))->assertRedirect();
});
test('authenticated user can view edit form', function () {
    $this->mock(IdpTenantService::class)
        ->shouldReceive('listTenants')
        ->once()
        ->andReturn([['id' => 1, 'name' => 'Idp Tenant', 'slug' => 'idp-tenant']]);

    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.tenants.edit', $this->tenant))
        ->assertOk()
        ->assertViewHas('tenant', fn($tenant) => $tenant->is($this->tenant))
        ->assertViewHas('idpTenants', [['id' => 1, 'name' => 'Idp Tenant', 'slug' => 'idp-tenant']]);
});
test('edit form degrades to empty list when idp call fails', function () {
    $this->mock(IdpTenantService::class)
        ->shouldReceive('listTenants')
        ->once()
        ->andThrow(new RuntimeException('IDP unreachable'));

    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.tenants.edit', $this->tenant))
        ->assertOk()
        ->assertViewHas('idpTenants', []);
});
test('update saves tenant details', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->put(route('landlord.tenants.update', $this->tenant), [
            'name' => 'Renamed Tenant',
            'idp_tenant_id' => '7',
        ])
        ->assertRedirect(route('landlord.tenants.index'))
        ->assertSessionHas('success');

    $this->tenant->refresh();
    expect($this->tenant->name)->toBe('Renamed Tenant');
    expect($this->tenant->idp_tenant_id)->toBe('7');
});
test('update allows clearing idp tenant id', function () {
    $this->tenant->update(['idp_tenant_id' => '7']);

    $this->actingAs($this->landlordUser, 'landlord')
        ->put(route('landlord.tenants.update', $this->tenant), [
            'name' => 'Renamed Tenant',
        ])
        ->assertRedirect(route('landlord.tenants.index'))
        ->assertSessionHas('success');

    expect($this->tenant->refresh()->idp_tenant_id)->toBeNull();
});
test('update requires name', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->put(route('landlord.tenants.update', $this->tenant), [
            'name' => '',
        ])
        ->assertSessionHasErrors('name');
});
test('update rejects idp tenant id already used by another tenant', function () {
    $otherTenant = new Tenant(['id' => 'other-tenant', 'name' => 'Other Tenant', 'idp_tenant_id' => '55']);
    $otherTenant->saveQuietly();

    $this->actingAs($this->landlordUser, 'landlord')
        ->put(route('landlord.tenants.update', $this->tenant), [
            'name' => 'Renamed Tenant',
            'idp_tenant_id' => '55',
        ])
        ->assertSessionHasErrors('idp_tenant_id');
});
test('update allows keeping own idp tenant id', function () {
    $this->tenant->update(['idp_tenant_id' => '7']);

    $this->actingAs($this->landlordUser, 'landlord')
        ->put(route('landlord.tenants.update', $this->tenant), [
            'name' => 'Renamed Tenant',
            'idp_tenant_id' => '7',
        ])
        ->assertRedirect(route('landlord.tenants.index'))
        ->assertSessionHas('success');
});
test('reset fails when name does not match', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.tenants.reset', $this->tenant), [
            'confirm_tenant_name' => 'WrongName',
        ])
        ->assertRedirect(route('landlord.tenants.index'))
        ->assertSessionHas('error');
});
test('reset succeeds when name matches', function () {
    $this->mock(TenantResetService::class)
        ->shouldReceive('reset')
        ->once();

    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.tenants.reset', $this->tenant), [
            'confirm_tenant_name' => 'Landlord Test Tenant',
        ])
        ->assertRedirect(route('landlord.tenants.index'))
        ->assertSessionHas('success');
});
test('reset redirects with error when service throws', function () {
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
});
test('destroy with delete database true dispatches job', function () {
    Bus::fake();

    $this->actingAs($this->landlordUser, 'landlord')
        ->delete(route('landlord.tenants.destroy', $this->tenant), [
            'confirm_tenant_name' => 'Landlord Test Tenant',
            'delete_database' => true,
        ])
        ->assertRedirect(route('landlord.tenants.index'))
        ->assertSessionHas('success');

    Bus::assertDispatched(DeleteDatabase::class);
});
test('guest cannot view select user page', function () {
    $this->get(route('landlord.tenants.select-user', $this->tenant))->assertRedirect();
});
test('authenticated landlord can view select user page', function () {
    $this->mock(TenantImpersonationService::class)
        ->shouldReceive('getTenantUsersPaginated')
        ->once()
        ->andReturn(new LengthAwarePaginator([], 0, 15, 1));

    $this->actingAs($this->landlordUser, 'landlord')
        ->get(route('landlord.tenants.select-user', $this->tenant))
        ->assertOk()
        ->assertViewIs('landlord.tenants.select-user')
        ->assertViewHas('users');
});
test('guest cannot post impersonate', function () {
    $this->post(route('landlord.impersonate', $this->tenant), [
        'user_identifier' => 'admin@example.com',
    ])->assertRedirect();
});
test('impersonate requires user identifier', function () {
    $this->actingAs($this->landlordUser, 'landlord')
        ->post(route('landlord.impersonate', $this->tenant), [])
        ->assertSessionHasErrors('user_identifier');
});
test('impersonate returns error when user not found', function () {
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
});
test('impersonate returns error when tenant has no domain', function () {
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
});
test('impersonate redirects to tenant domain url with token', function () {
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
});
