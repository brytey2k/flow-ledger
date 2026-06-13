<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Data\Auth\SsoUserClaimsDto;
use App\Services\SettingsService;
use App\Services\SsoClientService;
use App\Services\SsoUserProvisioningService;
use Illuminate\Support\Facades\Cache;
use Tests\TenantAppTestCase;

class SsoTest extends TenantAppTestCase
{
    // ── SsoFinalizeController ─────────────────────────────────────────────────

    public function test_finalize_returns_403_when_token_is_missing(): void
    {
        $this->get(route('sso.finalize'))
            ->assertForbidden();
    }

    public function test_finalize_returns_403_when_token_is_invalid(): void
    {
        $this->get(route('sso.finalize', ['token' => 'bad-token']))
            ->assertForbidden();
    }

    public function test_finalize_logs_in_existing_user_matched_by_oidc_sub(): void
    {
        $this->user->update(['oidc_sub' => 'sub-123', 'is_oidc_user' => true]);

        $claims = new SsoUserClaimsDto(
            sub: 'sub-123',
            email: $this->user->email,
            name: 'Test User',
            email_verified: true,
            tenant_id: '1',
            products: ['flow-ledger'],
        );

        $token = 'test-login-token-' . uniqid();
        Cache::put("sso_login:{$token}", $claims->toArray(), now()->addSeconds(30));

        $this->get(route('sso.finalize', ['token' => $token]))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_finalize_links_existing_password_user_by_email(): void
    {
        $this->user->update(['oidc_sub' => null, 'is_oidc_user' => false]);

        $claims = new SsoUserClaimsDto(
            sub: 'new-sub-456',
            email: $this->user->email,
            name: 'Test User',
            email_verified: true,
            tenant_id: '1',
            products: ['flow-ledger'],
        );

        $token = 'test-login-token-' . uniqid();
        Cache::put("sso_login:{$token}", $claims->toArray(), now()->addSeconds(30));

        $this->get(route('sso.finalize', ['token' => $token]))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($this->user);
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'oidc_sub' => 'new-sub-456',
            'is_oidc_user' => true,
        ]);
    }

    public function test_finalize_jit_provisions_new_tenant_user(): void
    {
        app(SettingsService::class)->setSsoDefaultBranch($this->branch->id);

        $claims = new SsoUserClaimsDto(
            sub: 'brand-new-sub-789',
            email: 'brand-new@example.com',
            name: 'Brand New',
            email_verified: true,
            tenant_id: '1',
            products: ['flow-ledger'],
        );

        $token = 'test-login-token-' . uniqid();
        Cache::put("sso_login:{$token}", $claims->toArray(), now()->addSeconds(30));

        $this->get(route('sso.finalize', ['token' => $token]))
            ->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('users', [
            'email' => 'brand-new@example.com',
            'oidc_sub' => 'brand-new-sub-789',
            'is_oidc_user' => true,
        ]);
    }

    public function test_finalize_token_is_consumed_after_use(): void
    {
        $claims = new SsoUserClaimsDto(
            sub: 'sub-123',
            email: $this->user->email,
            name: 'Test User',
            email_verified: true,
            tenant_id: '1',
            products: ['flow-ledger'],
        );

        $token = 'one-time-token-' . uniqid();
        Cache::put("sso_login:{$token}", $claims->toArray(), now()->addSeconds(30));

        $this->get(route('sso.finalize', ['token' => $token]))->assertRedirect();

        // Second use of the same token must fail
        $this->get(route('sso.finalize', ['token' => $token]))->assertForbidden();
    }

    // ── SsoUserProvisioningService ────────────────────────────────────────────

    public function test_provisioner_does_not_link_unverified_email(): void
    {
        $this->user->update(['oidc_sub' => null]);

        $claims = new SsoUserClaimsDto(
            sub: 'unverified-sub',
            email: $this->user->email,
            name: 'Test User',
            email_verified: false,
            tenant_id: '1',
            products: [],
        );

        $this->expectException(\App\Exceptions\UnverifiedEmailException::class);

        app(SsoUserProvisioningService::class)->findOrCreateTenantUser($claims);
    }

    // ── SsoClientService ─────────────────────────────────────────────────────

    public function test_pkce_challenge_is_s256_hash_of_verifier(): void
    {
        $service = app(SsoClientService::class);
        $pkce = $service->generatePkce();

        $expectedChallenge = rtrim(strtr(base64_encode(hash('sha256', $pkce['verifier'], true)), '+/', '-_'), '=');

        $this->assertSame($expectedChallenge, $pkce['challenge']);
    }

    public function test_state_is_valid_and_consumed_once(): void
    {
        $service = app(SsoClientService::class);
        $state = $service->generateState();

        $this->assertTrue($service->validateAndConsumeState($state));
        $this->assertFalse($service->validateAndConsumeState($state));
    }

    public function test_state_is_invalid_for_unknown_value(): void
    {
        $service = app(SsoClientService::class);
        $this->assertFalse($service->validateAndConsumeState('unknown-state'));
    }

    // ── SsoUserClaimsDto ─────────────────────────────────────────────────────

    public function test_claims_dto_identifies_landlord_user(): void
    {
        $claims = new SsoUserClaimsDto('sub', 'a@b.com', 'A', true, null, []);
        $this->assertTrue($claims->isLandlordUser());
    }

    public function test_claims_dto_identifies_tenant_user(): void
    {
        $claims = new SsoUserClaimsDto('sub', 'a@b.com', 'A', true, 'tenant-1', []);
        $this->assertFalse($claims->isLandlordUser());
    }

    public function test_claims_dto_checks_product_access(): void
    {
        $claims = new SsoUserClaimsDto('sub', 'a@b.com', 'A', true, '1', ['flow-ledger', 'other']);
        $this->assertTrue($claims->hasProductAccess('flow-ledger'));
        $this->assertFalse($claims->hasProductAccess('accounting'));
    }

    public function test_claims_dto_round_trips_via_array(): void
    {
        $original = new SsoUserClaimsDto('sub-abc', 'u@e.com', 'Full Name', true, 'tid-1', ['flow-ledger']);
        $restored = SsoUserClaimsDto::fromArray($original->toArray());

        $this->assertSame($original->sub, $restored->sub);
        $this->assertSame($original->email, $restored->email);
        $this->assertSame($original->name, $restored->name);
        $this->assertSame($original->email_verified, $restored->email_verified);
        $this->assertSame($original->tenant_id, $restored->tenant_id);
        $this->assertSame($original->products, $restored->products);
    }

    public function test_claims_dto_splits_name_correctly(): void
    {
        $claims = new SsoUserClaimsDto('s', 'e@e.com', 'John Doe Smith', true, '1', []);
        $parts = $claims->splitName();

        $this->assertSame('John', $parts['first_name']);
        $this->assertSame('Doe Smith', $parts['last_name']);
    }
}
