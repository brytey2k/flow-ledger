<?php

declare(strict_types=1);

namespace App\Services;

use App\Exceptions\InvalidProvisionTokenException;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;

/**
 * Verifies tenant-provisioning JWTs minted by the IAM IdP with its Passport
 * private key. Mirrors the validation performed by IamJwtGuard: RS256
 * signature against the IdP's JWKS public key, issuer, expiry, and an `aud`
 * claim that must contain this product's slug.
 */
class IamProvisionTokenVerifier
{
    public function __construct(private readonly SsoClientService $ssoClient) {}

    /**
     * @param non-empty-string $rawToken
     *
     * @throws InvalidProvisionTokenException
     *
     * @return array<string, mixed> the token's claims
     */
    public function verify(string $rawToken): array
    {
        $pem = $this->ssoClient->getIdpPublicKeyPem();

        if ($pem === '') {
            throw new InvalidProvisionTokenException('JWKS public key unavailable.');
        }

        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText('verify-only'),
            InMemory::plainText($pem),
        );

        $issuer = rtrim(config()->string('sso.idp_url'), '/');

        if ($issuer === '') {
            throw new InvalidProvisionTokenException('SSO IDP URL is not configured.');
        }

        $configuration->setValidationConstraints(
            new SignedWith($configuration->signer(), $configuration->verificationKey()),
            new IssuedBy($issuer),
        );

        try {
            $token = $configuration->parser()->parse($rawToken);
        } catch (\Throwable $e) {
            throw new InvalidProvisionTokenException('Malformed provision token.', previous: $e);
        }

        if (! $token instanceof Plain) {
            throw new InvalidProvisionTokenException('Encrypted tokens are not supported.');
        }

        try {
            $configuration->validator()->assert($token, ...$configuration->validationConstraints());
        } catch (RequiredConstraintsViolated $e) {
            throw new InvalidProvisionTokenException($e->getMessage());
        }

        if ($token->isExpired(now()->toDateTimeImmutable())) {
            throw new InvalidProvisionTokenException('Provision token has expired.');
        }

        /** @var list<string> $aud */
        $aud = (array) $token->claims()->get('aud', []);

        if (! in_array(config()->string('sso.product_slug'), $aud, true)) {
            throw new InvalidProvisionTokenException('Provision token audience does not match this product.');
        }

        return $token->claims()->all();
    }
}
