<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Auth\SsoUserClaimsDto;
use App\Exceptions\UnverifiedEmailException;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant\User as TenantUser;
use Illuminate\Support\Str;
use RuntimeException;

class SsoUserProvisioningService
{
    public function __construct(
        private readonly SettingsService $settingsService,
    ) {}

    /**
     * Find or JIT-provision a tenant user from SSO claims.
     * Must be called after tenancy has been initialized for the correct tenant.
     *
     * @param SsoUserClaimsDto $claims
     */
    public function findOrCreateTenantUser(SsoUserClaimsDto $claims): TenantUser
    {
        // 1. Returning user — match by oidc_sub (most common path)
        $user = TenantUser::query()->where('oidc_sub', $claims->sub)->first();
        if ($user !== null) {
            return $user;
        }

        // 2. Existing password user — link their account to the IDP subject.
        // Only safe when the IdP has verified the email; without this guard an
        // unverified email from the IdP could be used to hijack an existing account.
        $user = TenantUser::query()->where('email', $claims->email)->first();
        if ($user !== null) {
            if (! $claims->email_verified) {
                throw new UnverifiedEmailException();
            }

            $user->update(['oidc_sub' => $claims->sub, 'is_oidc_user' => true]);

            return $user->refresh();
        }

        // 3. First-time SSO user — provision in the tenant database
        $nameParts = $claims->splitName();

        return TenantUser::create([
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'] ?? $nameParts['first_name'],
            'email' => $claims->email,
            'password' => bcrypt(Str::random(32)),
            'oidc_sub' => $claims->sub,
            'is_oidc_user' => true,
            'branch_id' => $this->resolveDefaultBranchId(),
            'operational_branch_id' => $this->resolveDefaultBranchId(),
        ]);
    }

    /**
     * Find or JIT-provision a landlord user from SSO claims.
     * Operates on the central database — no tenant context required.
     *
     * @param SsoUserClaimsDto $claims
     */
    public function findOrCreateLandlordUser(SsoUserClaimsDto $claims): LandlordUser
    {
        // 1. Returning landlord user
        $user = LandlordUser::query()->where('oidc_sub', $claims->sub)->first();
        if ($user !== null) {
            return $user;
        }

        // 2. Existing password-based landlord user — link account.
        $user = LandlordUser::query()->where('email', $claims->email)->first();
        if ($user !== null) {
            if (! $claims->email_verified) {
                throw new UnverifiedEmailException();
            }

            $user->update(['oidc_sub' => $claims->sub, 'is_oidc_user' => true]);

            return $user->refresh();
        }

        // 3. New landlord user (first SSO login)
        return LandlordUser::create([
            'name' => $claims->name,
            'email' => $claims->email,
            'password' => bcrypt(Str::random(32)),
            'oidc_sub' => $claims->sub,
            'is_oidc_user' => true,
        ]);
    }

    private function resolveDefaultBranchId(): int
    {
        $branchId = $this->settingsService->getSsoDefaultBranchId();

        if ($branchId === null) {
            throw new RuntimeException(
                'No default branch is configured for SSO provisioning. ' .
                'Please set one in Settings → SSO.',
            );
        }

        return $branchId;
    }
}
