<?php

declare(strict_types=1);

namespace Tests\Feature\Api\Tenant;

use App\Auth\IamJwtGuard;
use App\Repositories\UserRepository;
use App\Services\SsoClientService;
use DateTimeImmutable;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Tests\TenantAppTestCase;

/**
 * Exercises the REAL IamJwtGuard::parseAndValidate() end-to-end by minting an
 * RS256 JWT with a locally generated RSA keypair and stubbing only the public
 * key source (SsoClientService::getIdpPublicKeyPem()). Everything else in the
 * guard runs for real: signature verification, issuer, audience, product and
 * expiry checks, plus user resolution via UserRepository.
 */
class IamJwtGuardValidationTest extends TenantAppTestCase
{
    private const IDP_URL = 'http://iam.test:81';

    private const PRODUCT = 'flow-ledger';

    private const SUB = 'oidc-sub-real-token';

    private string $privatePem;

    private string $publicPem;

    protected function setUp(): void
    {
        parent::setUp();

        [$this->privatePem, $this->publicPem] = $this->generateRsaKeypair();

        config([
            'sso.idp_url' => self::IDP_URL,
            'sso.audience' => self::PRODUCT,
            'sso.product_slug' => self::PRODUCT,
        ]);
    }

    public function test_valid_token_resolves_the_user(): void
    {
        $this->user->forceFill(['oidc_sub' => self::SUB])->save();

        $token = $this->mintToken();
        $guard = $this->makeGuard($token);

        $resolved = $guard->user();

        $this->assertNotNull($resolved);
        $this->assertSame($this->user->id, $resolved->getAuthIdentifier());
        $this->assertTrue($guard->check());
    }

    public function test_token_with_wrong_issuer_is_rejected(): void
    {
        $this->user->forceFill(['oidc_sub' => self::SUB])->save();

        $token = $this->mintToken(issuer: 'http://evil-idp.test');
        $guard = $this->makeGuard($token);

        $this->assertNull($guard->user());
    }

    public function test_token_without_matching_audience_is_rejected(): void
    {
        $this->user->forceFill(['oidc_sub' => self::SUB])->save();

        $token = $this->mintToken(audience: 'some-other-product');
        $guard = $this->makeGuard($token);

        $this->assertNull($guard->user());
    }

    public function test_token_without_matching_product_is_rejected(): void
    {
        $this->user->forceFill(['oidc_sub' => self::SUB])->save();

        $token = $this->mintToken(products: ['some-other-product']);
        $guard = $this->makeGuard($token);

        $this->assertNull($guard->user());
    }

    public function test_expired_token_is_rejected(): void
    {
        $this->user->forceFill(['oidc_sub' => self::SUB])->save();

        $now = new DateTimeImmutable('2026-07-04T12:00:00+00:00');
        $token = $this->mintToken(
            issuedAt: $now->modify('-2 hours'),
            expiresAt: $now->modify('-1 hour'),
        );
        $guard = $this->makeGuard($token);

        $this->assertNull($guard->user());
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param list<string> $products
     */
    private function mintToken(
        string|null $issuer = null,
        string|null $audience = null,
        array $products = [self::PRODUCT],
        string $subject = self::SUB,
        DateTimeImmutable|null $issuedAt = null,
        DateTimeImmutable|null $expiresAt = null,
    ): string {
        $now = new DateTimeImmutable('2026-07-04T12:00:00+00:00');

        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText($this->privatePem),
            InMemory::plainText($this->publicPem),
        );

        $token = $configuration->builder()
            ->withHeader('kid', '1')
            ->issuedBy($issuer ?? self::IDP_URL)
            ->permittedFor($audience ?? self::PRODUCT)
            ->relatedTo($subject)
            ->issuedAt($issuedAt ?? $now)
            ->expiresAt($expiresAt ?? $now->modify('+1 hour'))
            ->withClaim('products', $products)
            ->withClaim('scopes', ['openid', 'profile'])
            ->getToken($configuration->signer(), $configuration->signingKey());

        return $token->toString();
    }

    private function makeGuard(string $rawToken): IamJwtGuard
    {
        $sso = $this->createPartialMock(SsoClientService::class, ['getIdpPublicKeyPem']);
        $sso->method('getIdpPublicKeyPem')->willReturn($this->publicPem);

        $request = Request::create('/api/me', 'GET', server: [
            'HTTP_AUTHORIZATION' => 'Bearer ' . $rawToken,
        ]);

        return new IamJwtGuard(null, $request, $sso, $this->app->make(UserRepository::class));
    }

    /**
     * @return array{0: string, 1: string} [privatePem, publicPem]
     */
    private function generateRsaKeypair(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            $this->fail('Unable to generate RSA keypair for test.');
        }

        openssl_pkey_export($resource, $privatePem);

        $details = openssl_pkey_get_details($resource);
        $publicPem = is_array($details) ? (string) $details['key'] : '';

        return [$privatePem, $publicPem];
    }
}
