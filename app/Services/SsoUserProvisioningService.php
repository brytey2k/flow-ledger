<?php

declare(strict_types=1);

namespace App\Services;

use App\Data\Auth\SsoUserClaimsDto;
use App\Enums\Tenant\UserStatus;
use App\Exceptions\LandlordAccessDeniedException;
use App\Exceptions\UnverifiedEmailException;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant\Staff;
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
        // 1. Returning user — match by oidc_sub (most common path).
        // Revalidate email_verified on every login: the IDP's view of the
        // account can change after the initial link (e.g. flagged/unverified).
        $user = TenantUser::query()->where('oidc_sub', $claims->sub)->first();
        if ($user !== null) {
            if (! $claims->email_verified) {
                throw new UnverifiedEmailException();
            }

            if ($user->status === UserStatus::Invited) {
                $user->update(['status' => UserStatus::Active, 'activated_at' => now()]);
                $user = $user->refresh();
            }

            $this->maybeLinkStaffProfile($user, $claims);

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

            $update = ['oidc_sub' => $claims->sub, 'is_oidc_user' => true];
            if ($user->status === UserStatus::Invited) {
                $update['status'] = UserStatus::Active;
                $update['activated_at'] = now();
            }

            $user->update($update);
            $user = $user->refresh();
            $this->maybeLinkStaffProfile($user, $claims);

            return $user;
        }

        // 3. First-time SSO user — provision in the tenant database
        $nameParts = $claims->splitName();

        $user = TenantUser::create([
            'first_name' => $nameParts['first_name'],
            'last_name' => $nameParts['last_name'] ?? $nameParts['first_name'],
            'email' => $claims->email,
            'password' => bcrypt(Str::random(32)),
            'oidc_sub' => $claims->sub,
            'is_oidc_user' => true,
            'branch_id' => $this->resolveDefaultBranchId(),
            'operational_branch_id' => $this->resolveDefaultBranchId(),
        ]);

        $this->maybeLinkStaffProfile($user, $claims);

        return $user;
    }

    /**
     * Find the landlord user for an SSO identity that carries no tenant. The identity provider does not
     * mark platform administrators, so a missing tenant is only a hint: access is granted only to
     * identities that already exist as landlord users here. Nobody is created on first sight.
     * Operates on the central database, with no tenant context required.
     *
     * @param SsoUserClaimsDto $claims
     *
     * @throws LandlordAccessDeniedException when the identity is not a known landlord user
     * @throws UnverifiedEmailException when the identity provider has not verified the email
     */
    public function findLandlordUser(SsoUserClaimsDto $claims): LandlordUser
    {
        // 1. Returning landlord user - revalidate email_verified on every login.
        $user = LandlordUser::query()->where('oidc_sub', $claims->sub)->first();
        if ($user !== null) {
            if (! $claims->email_verified) {
                throw new UnverifiedEmailException();
            }

            return $user;
        }

        // 2. Existing password-based landlord user created by an operator - link the account.
        $user = LandlordUser::query()->where('email', $claims->email)->first();
        if ($user !== null) {
            if (! $claims->email_verified) {
                throw new UnverifiedEmailException();
            }

            $user->update(['oidc_sub' => $claims->sub, 'is_oidc_user' => true]);

            return $user->refresh();
        }

        throw new LandlordAccessDeniedException(__('auth.landlord_not_authorised'));
    }

    private function maybeLinkStaffProfile(TenantUser $user, SsoUserClaimsDto $claims): void
    {
        $staffRoleName = $this->settingsService->getSsoStaffRoleName();

        if ($staffRoleName === null || ! $claims->hasRole($staffRoleName)) {
            return;
        }

        $staff = Staff::query()
            ->where('email', $claims->email)
            ->whereNull('user_id')
            ->first();

        if ($staff !== null) {
            $staff->update(['user_id' => $user->id]);
        }
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
