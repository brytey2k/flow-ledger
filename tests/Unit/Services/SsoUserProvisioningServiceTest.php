<?php

declare(strict_types=1);

uses(Tests\TenantAppTestCase::class);
use App\Data\Auth\SsoUserClaimsDto;
use App\Enums\Tenant\UserStatus;
use App\Exceptions\LandlordAccessDeniedException;
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
test('find or create tenant user activates an invited user matched by oidc sub', function () {
    $existing = TenantUser::factory()->create([
        'oidc_sub' => 'sub-invited',
        'is_oidc_user' => true,
        'status' => UserStatus::Invited,
        'invited_at' => now(),
    ]);

    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-invited', email: $existing->email);

    $user = $this->service->findOrCreateTenantUser($claims);

    expect($user->status)->toBe(UserStatus::Active);
    expect($user->activated_at)->not->toBeNull();
});
test('find or create tenant user activates an invited user matched by email', function () {
    $existing = TenantUser::factory()->create([
        'oidc_sub' => null,
        'is_oidc_user' => true,
        'status' => UserStatus::Invited,
        'invited_at' => now(),
    ]);

    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-fresh-link', email: $existing->email);

    $user = $this->service->findOrCreateTenantUser($claims);

    expect($user->id)->toBe($existing->id);
    expect($user->status)->toBe(UserStatus::Active);
    expect($user->oidc_sub)->toBe('sub-fresh-link');
    expect($user->activated_at)->not->toBeNull();
});
test('find landlord user returns existing user matched by oidc sub', function () {
    $existing = LandlordUser::query()->create([
        'name' => 'Existing Landlord',
        'email' => 'existing-landlord@example.com',
        'password' => Hash::make('password'),
        'oidc_sub' => 'sub-landlord-existing',
        'is_oidc_user' => true,
    ]);

    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-landlord-existing', email: $existing->email);

    $user = $this->service->findLandlordUser($claims);

    expect($user->id)->toBe($existing->id);
});
test('find landlord user throws when returning oidc user email becomes unverified', function () {
    LandlordUser::query()->create([
        'name' => 'Revoked Landlord',
        'email' => 'revoked-landlord@example.com',
        'password' => Hash::make('password'),
        'oidc_sub' => 'sub-landlord-revoked',
        'is_oidc_user' => true,
    ]);

    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-landlord-revoked', email: 'irrelevant@example.com', emailVerified: false);

    $this->expectException(UnverifiedEmailException::class);

    $this->service->findLandlordUser($claims);
});
test('find landlord user refuses an unknown identity instead of creating an account', function () {
    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-stranger', email: 'stranger@example.com');

    expect(fn() => $this->service->findLandlordUser($claims))->toThrow(LandlordAccessDeniedException::class);
    expect(LandlordUser::query()->where('email', 'stranger@example.com')->exists())->toBeFalse();
});
test('find landlord user links an operator-created account by verified email', function () {
    $existing = LandlordUser::query()->create([
        'name' => 'Operator', 'email' => 'operator@example.com', 'password' => Hash::make('password'),
    ]);

    $user = $this->service->findLandlordUser(makeClaimsForSsoUserProvisioningService(sub: 'sub-operator', email: 'operator@example.com'));

    expect($user->id)->toBe($existing->id)->and($user->oidc_sub)->toBe('sub-operator');
});
test('find landlord user refuses to link by an unverified email', function () {
    LandlordUser::query()->create(['name' => 'Operator', 'email' => 'operator2@example.com', 'password' => Hash::make('password')]);

    $claims = makeClaimsForSsoUserProvisioningService(sub: 'sub-op2', email: 'operator2@example.com', emailVerified: false);

    expect(fn() => $this->service->findLandlordUser($claims))->toThrow(UnverifiedEmailException::class);
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
