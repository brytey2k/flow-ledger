<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Data\Auth\SsoUserClaimsDto;
use App\Models\Tenant\Staff;
use App\Services\SettingsService;
use App\Services\SsoClientService;
use App\Services\SsoUserProvisioningService;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    // The automated-tests tenant fixture doesn't set idp_tenant_id; the
    // finalize controller now requires it to match the SSO claims.
    $this->tenant->forceFill(['idp_tenant_id' => 'idp-' . $this->tenant->id])->save();
});
/**
 * Mirrors SsoController::routeToTenant(), which writes this token on the
 * central domain via the explicit store name — bypassing Stancl's
 * tenant cache tagging, since the finalize request that reads it back
 * arrives on a tenant subdomain where tenancy has already tagged the
 * Cache facade. The key is scoped to the tenant it was minted for.
 *
 * @param string $token
 * @param SsoUserClaimsDto $claims
 */
function storeSsoTokenForSso(string $token, SsoUserClaimsDto $claims): void
{
    $tenant = test()->tenant;

    Cache::store(config()->string('cache.default'))
        ->put("sso_login:{$tenant->id}:{$token}", $claims->toArray(), now()->addSeconds(30));
}
test('finalize returns 403 when token is missing', function () {
    $this->get(route('sso.finalize'))
        ->assertForbidden();
});
test('finalize returns 403 when token is invalid', function () {
    $this->get(route('sso.finalize', ['token' => 'bad-token']))
        ->assertForbidden();
});
test('finalize logs in existing user matched by oidc sub', function () {
    $this->user->update(['oidc_sub' => 'sub-123', 'is_oidc_user' => true]);

    $claims = new SsoUserClaimsDto(
        sub: 'sub-123',
        email: $this->user->email,
        name: 'Test User',
        email_verified: true,
        tenant_id: $this->tenant->idp_tenant_id,
        products: ['flow-ledger'],
    );

    $token = 'test-login-token-' . uniqid();
    storeSsoTokenForSso($token, $claims);

    $this->get(route('sso.finalize', ['token' => $token]))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);
});
test('finalize links existing password user by email', function () {
    $this->user->update(['oidc_sub' => null, 'is_oidc_user' => false]);

    $claims = new SsoUserClaimsDto(
        sub: 'new-sub-456',
        email: $this->user->email,
        name: 'Test User',
        email_verified: true,
        tenant_id: $this->tenant->idp_tenant_id,
        products: ['flow-ledger'],
    );

    $token = 'test-login-token-' . uniqid();
    storeSsoTokenForSso($token, $claims);

    $this->get(route('sso.finalize', ['token' => $token]))
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($this->user);
    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'oidc_sub' => 'new-sub-456',
        'is_oidc_user' => true,
    ]);
});
test('finalize jit provisions new tenant user', function () {
    app(SettingsService::class)->setSsoDefaultBranch($this->branch->id);

    $claims = new SsoUserClaimsDto(
        sub: 'brand-new-sub-789',
        email: 'brand-new@example.com',
        name: 'Brand New',
        email_verified: true,
        tenant_id: $this->tenant->idp_tenant_id,
        products: ['flow-ledger'],
    );

    $token = 'test-login-token-' . uniqid();
    storeSsoTokenForSso($token, $claims);

    $this->get(route('sso.finalize', ['token' => $token]))
        ->assertRedirect(route('dashboard'));

    $this->assertDatabaseHas('users', [
        'email' => 'brand-new@example.com',
        'oidc_sub' => 'brand-new-sub-789',
        'is_oidc_user' => true,
    ]);
});
test('finalize token is consumed after use', function () {
    $claims = new SsoUserClaimsDto(
        sub: 'sub-123',
        email: $this->user->email,
        name: 'Test User',
        email_verified: true,
        tenant_id: $this->tenant->idp_tenant_id,
        products: ['flow-ledger'],
    );

    $token = 'one-time-token-' . uniqid();
    storeSsoTokenForSso($token, $claims);

    $this->get(route('sso.finalize', ['token' => $token]))->assertRedirect();

    // Second use of the same token must fail
    $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
});
test('provisioner does not link unverified email', function () {
    $this->user->update(['oidc_sub' => null]);

    $claims = new SsoUserClaimsDto(
        sub: 'unverified-sub',
        email: $this->user->email,
        name: 'Test User',
        email_verified: false,
        tenant_id: '1',
        products: [],
    );

    $this->expectException(App\Exceptions\UnverifiedEmailException::class);

    app(SsoUserProvisioningService::class)->findOrCreateTenantUser($claims);
});
test('pkce challenge is s256 hash of verifier', function () {
    $service = app(SsoClientService::class);
    $pkce = $service->generatePkce();

    $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $pkce['verifier'], true)), '+/', '-_'), '=');

    expect($pkce['challenge'])->toBe($expectedChallenge);
});
test('state is valid and consumed once', function () {
    $service = app(SsoClientService::class);
    $state = $service->generateState();

    expect($service->validateAndConsumeState($state))->toBeTrue();
    expect($service->validateAndConsumeState($state))->toBeFalse();
});
test('state is invalid for unknown value', function () {
    $service = app(SsoClientService::class);
    expect($service->validateAndConsumeState('unknown-state'))->toBeFalse();
});
test('authorization url includes resource indicator', function () {
    config([
        'sso.idp_url' => 'https://idp.test',
        'sso.client_id' => 'test-client',
        'sso.redirect_uri' => 'https://app.test/auth/sso/callback',
        'sso.scopes' => ['openid', 'email', 'profile'],
        'sso.product_slug' => 'flow-ledger',
    ]);

    $url = app(SsoClientService::class)->buildAuthorizationUrl('the-state', 'the-challenge');

    $this->assertStringContainsString('resource=flow-ledger', $url);

    parse_str((string) parse_url($url, PHP_URL_QUERY), $params);
    expect($params['resource'] ?? null)->toBe('flow-ledger');
});
test('token exchange sends resource indicator in body', function () {
    config([
        'sso.idp_internal_url' => 'https://idp-internal.test',
        'sso.client_id' => 'test-client',
        'sso.client_secret' => 'test-secret',
        'sso.redirect_uri' => 'https://app.test/auth/sso/callback',
        'sso.product_slug' => 'flow-ledger',
        'sso.verify_ssl' => false,
    ]);

    Http::fake([
        'https://idp-internal.test/oauth/token' => Http::response([
            'access_token' => 'fake-access-token',
            'token_type' => 'Bearer',
        ]),
    ]);

    $tokens = app(SsoClientService::class)->exchangeCodeForTokens('the-code', 'the-verifier');

    expect($tokens['access_token'])->toBe('fake-access-token');

    Http::assertSent(fn(ClientRequest $request): bool => $request->url() === 'https://idp-internal.test/oauth/token'
            && $request['resource'] === 'flow-ledger'
            && $request['grant_type'] === 'authorization_code'
            && $request['code'] === 'the-code');
});
test('claims dto identifies landlord user', function () {
    $claims = new SsoUserClaimsDto('sub', 'a@b.com', 'A', true, null, []);
    expect($claims->isLandlordUser())->toBeTrue();
});
test('claims dto identifies tenant user', function () {
    $claims = new SsoUserClaimsDto('sub', 'a@b.com', 'A', true, 'tenant-1', []);
    expect($claims->isLandlordUser())->toBeFalse();
});
test('claims dto checks product access', function () {
    $claims = new SsoUserClaimsDto('sub', 'a@b.com', 'A', true, '1', ['flow-ledger', 'other']);
    expect($claims->hasProductAccess('flow-ledger'))->toBeTrue();
    expect($claims->hasProductAccess('accounting'))->toBeFalse();
});
test('claims dto round trips via array', function () {
    $original = new SsoUserClaimsDto('sub-abc', 'u@e.com', 'Full Name', true, 'tid-1', ['flow-ledger']);
    $restored = SsoUserClaimsDto::fromArray($original->toArray());

    expect($restored->sub)->toBe($original->sub);
    expect($restored->email)->toBe($original->email);
    expect($restored->name)->toBe($original->name);
    expect($restored->email_verified)->toBe($original->email_verified);
    expect($restored->tenant_id)->toBe($original->tenant_id);
    expect($restored->products)->toBe($original->products);
});
test('claims dto splits name correctly', function () {
    $claims = new SsoUserClaimsDto('s', 'e@e.com', 'John Doe Smith', true, '1', []);
    $parts = $claims->splitName();

    expect($parts['first_name'])->toBe('John');
    expect($parts['last_name'])->toBe('Doe Smith');
});
test('claims dto checks role membership', function () {
    $claims = new SsoUserClaimsDto('sub', 'a@b.com', 'A', true, '1', [], ['flow-ledger-staff', 'viewer']);
    expect($claims->hasRole('flow-ledger-staff'))->toBeTrue();
    expect($claims->hasRole('admin'))->toBeFalse();
});
test('claims dto round trips roles via array', function () {
    $original = new SsoUserClaimsDto('sub', 'a@b.com', 'A', true, '1', [], ['flow-ledger-staff']);
    $restored = SsoUserClaimsDto::fromArray($original->toArray());

    expect($restored->roles)->toBe($original->roles);
});
test('staff role user links to existing unlinked staff by email', function () {
    app(SettingsService::class)->setSsoDefaultBranch($this->branch->id);
    app(SettingsService::class)->setSsoStaffRoleName('flow-ledger-staff');

    $staff = Staff::factory()->create(['email' => 'jane@example.com', 'user_id' => null]);

    $claims = new SsoUserClaimsDto(
        sub: 'jane-sub-001',
        email: 'jane@example.com',
        name: 'Jane Doe',
        email_verified: true,
        tenant_id: '1',
        products: ['flow-ledger'],
        roles: ['flow-ledger-staff'],
    );

    $user = app(SsoUserProvisioningService::class)->findOrCreateTenantUser($claims);

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'user_id' => $user->id,
    ]);
});
test('staff role user skips linking when no matching staff exists', function () {
    app(SettingsService::class)->setSsoDefaultBranch($this->branch->id);
    app(SettingsService::class)->setSsoStaffRoleName('flow-ledger-staff');

    $claims = new SsoUserClaimsDto(
        sub: 'no-staff-sub',
        email: 'nostaff@example.com',
        name: 'No Staff',
        email_verified: true,
        tenant_id: '1',
        products: ['flow-ledger'],
        roles: ['flow-ledger-staff'],
    );

    $user = app(SsoUserProvisioningService::class)->findOrCreateTenantUser($claims);

    $this->assertDatabaseHas('users', ['id' => $user->id]);
    $this->assertDatabaseMissing('staff', ['user_id' => $user->id]);
});
test('staff role user skips linking when staff already has user id', function () {
    app(SettingsService::class)->setSsoDefaultBranch($this->branch->id);
    app(SettingsService::class)->setSsoStaffRoleName('flow-ledger-staff');

    $existingUser = $this->user;
    $staff = Staff::factory()->create(['email' => 'taken@example.com', 'user_id' => $existingUser->id]);

    $claims = new SsoUserClaimsDto(
        sub: 'new-sub-for-taken',
        email: 'taken@example.com',
        name: 'Taken Staff',
        email_verified: true,
        tenant_id: '1',
        products: ['flow-ledger'],
        roles: ['flow-ledger-staff'],
    );

    $user = app(SsoUserProvisioningService::class)->findOrCreateTenantUser($claims);

    // Staff should still point to the original user, not the new provisioned one
    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'user_id' => $existingUser->id,
    ]);
});
test('non staff role user does not trigger staff linking', function () {
    app(SettingsService::class)->setSsoDefaultBranch($this->branch->id);
    app(SettingsService::class)->setSsoStaffRoleName('flow-ledger-staff');

    $staff = Staff::factory()->create(['email' => 'client@example.com', 'user_id' => null]);

    $claims = new SsoUserClaimsDto(
        sub: 'client-sub-001',
        email: 'client@example.com',
        name: 'Client User',
        email_verified: true,
        tenant_id: '1',
        products: ['flow-ledger'],
        roles: ['viewer'],
    );

    app(SsoUserProvisioningService::class)->findOrCreateTenantUser($claims);

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'user_id' => null,
    ]);
});
test('staff linking skipped when sso staff role not configured', function () {
    app(SettingsService::class)->setSsoDefaultBranch($this->branch->id);

    // Deliberately NOT configuring sso_staff_role_name
    $staff = Staff::factory()->create(['email' => 'unconfigured@example.com', 'user_id' => null]);

    $claims = new SsoUserClaimsDto(
        sub: 'unconfigured-sub',
        email: 'unconfigured@example.com',
        name: 'Unconfigured',
        email_verified: true,
        tenant_id: '1',
        products: ['flow-ledger'],
        roles: ['flow-ledger-staff'],
    );

    app(SsoUserProvisioningService::class)->findOrCreateTenantUser($claims);

    $this->assertDatabaseHas('staff', [
        'id' => $staff->id,
        'user_id' => null,
    ]);
});
