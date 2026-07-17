<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Data\Auth\SsoUserClaimsDto;
use App\Exceptions\UnverifiedEmailException;
use App\Models\Landlord\User as LandlordUser;
use App\Models\Tenant\User as TenantUser;
use App\Services\SsoUserProvisioningService;
use Illuminate\Support\Facades\Hash;
use PHPUnit\Framework\Attributes\Test;
use Tests\TenantAppTestCase;

class SsoUserProvisioningServiceTest extends TenantAppTestCase
{
    private SsoUserProvisioningService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SsoUserProvisioningService::class);
    }

    #[Test]
    public function find_or_create_tenant_user_returns_existing_user_matched_by_oidc_sub(): void
    {
        $existing = TenantUser::factory()->create([
            'oidc_sub' => 'sub-existing',
            'is_oidc_user' => true,
        ]);

        $claims = $this->makeClaims(sub: 'sub-existing', email: $existing->email);

        $user = $this->service->findOrCreateTenantUser($claims);

        $this->assertSame($existing->id, $user->id);
    }

    #[Test]
    public function find_or_create_tenant_user_throws_when_returning_oidc_user_email_becomes_unverified(): void
    {
        TenantUser::factory()->create([
            'oidc_sub' => 'sub-revoked',
            'is_oidc_user' => true,
        ]);

        $claims = $this->makeClaims(sub: 'sub-revoked', email: 'irrelevant@example.com', emailVerified: false);

        $this->expectException(UnverifiedEmailException::class);

        $this->service->findOrCreateTenantUser($claims);
    }

    #[Test]
    public function find_or_create_landlord_user_returns_existing_user_matched_by_oidc_sub(): void
    {
        $existing = LandlordUser::query()->create([
            'name' => 'Existing Landlord',
            'email' => 'existing-landlord@example.com',
            'password' => Hash::make('password'),
            'oidc_sub' => 'sub-landlord-existing',
            'is_oidc_user' => true,
        ]);

        $claims = $this->makeClaims(sub: 'sub-landlord-existing', email: $existing->email);

        $user = $this->service->findOrCreateLandlordUser($claims);

        $this->assertSame($existing->id, $user->id);
    }

    #[Test]
    public function find_or_create_landlord_user_throws_when_returning_oidc_user_email_becomes_unverified(): void
    {
        LandlordUser::query()->create([
            'name' => 'Revoked Landlord',
            'email' => 'revoked-landlord@example.com',
            'password' => Hash::make('password'),
            'oidc_sub' => 'sub-landlord-revoked',
            'is_oidc_user' => true,
        ]);

        $claims = $this->makeClaims(sub: 'sub-landlord-revoked', email: 'irrelevant@example.com', emailVerified: false);

        $this->expectException(UnverifiedEmailException::class);

        $this->service->findOrCreateLandlordUser($claims);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeClaims(
        string $sub,
        string $email,
        string $name = 'Jane Doe',
        bool $emailVerified = true,
    ): SsoUserClaimsDto {
        return new SsoUserClaimsDto(
            sub: $sub,
            email: $email,
            name: $name,
            email_verified: $emailVerified,
            tenant_id: $this->tenant->id,
            products: ['flow-ledger'],
            roles: [],
        );
    }
}
