<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Data\Auth\SsoUserClaimsDto;
use App\Exceptions\UnverifiedEmailException;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant\User as TenantUser;
use App\Services\SsoUserProvisioningService;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->service = app(SsoUserProvisioningService::class);
});
test('find or create tenant user returns existing user matched by oidc sub', function () {
    $existing = TenantUser::factory()->create([
        'oidc_sub' => 'sub-existing',
        'is_oidc_user' => true,
    ]);

    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-existing', email: $existing->email);

    $user = $this->service->findOrCreateTenantUser($claims);

    expect($user->id)->toBe($existing->id);
});
test('find or create tenant user throws when returning oidc user email becomes unverified', function () {
    TenantUser::factory()->create([
        'oidc_sub' => 'sub-revoked',
        'is_oidc_user' => true,
    ]);

    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-revoked', email: 'irrelevant@example.com', emailVerified: false);

    $this->expectException(UnverifiedEmailException::class);

    $this->service->findOrCreateTenantUser($claims);
});
test('find or create landlord user returns existing user matched by oidc sub', function () {
    $existing = LandlordUser::query()->create([
        'name' => 'Existing Landlord',
        'email' => 'existing-landlord@example.com',
        'password' => Hash::make('password'),
        'oidc_sub' => 'sub-landlord-existing',
        'is_oidc_user' => true,
    ]);

    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-landlord-existing', email: $existing->email);

    $user = $this->service->findOrCreateLandlordUser($claims);

    expect($user->id)->toBe($existing->id);
});
test('find or create landlord user throws when returning oidc user email becomes unverified', function () {
    LandlordUser::query()->create([
        'name' => 'Revoked Landlord',
        'email' => 'revoked-landlord@example.com',
        'password' => Hash::make('password'),
        'oidc_sub' => 'sub-landlord-revoked',
        'is_oidc_user' => true,
    ]);

    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-landlord-revoked', email: 'irrelevant@example.com', emailVerified: false);

    $this->expectException(UnverifiedEmailException::class);

    $this->service->findOrCreateLandlordUser($claims);
});
// -------------------------------------------------------------------------
// Helpers
// -------------------------------------------------------------------------
function makeClaimsForSsoUserProvisioningService(string $sub, string $email, string $name = 'Jane Doe', bool $emailVerified = true): SsoUserClaimsDto
{
    return new SsoUserClaimsDto(
        sub: $sub,
        email: $email,
        name: $name,
        email_verified: $emailVerified,
        tenant_id: test()->tenant->id,
        products: ['flow-ledger'],
        roles: [],
    );
}
