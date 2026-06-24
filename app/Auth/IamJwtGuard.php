<?php

declare(strict_types=1);

namespace App\Auth;

use App\Repositories\UserRepository;
use App\Services\SsoClientService;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use RuntimeException;

class IamJwtGuard implements Guard
{
    private Authenticatable|null $user = null;

    private bool $userResolved = false;

    public function __construct(
        private readonly Request $request,
        private readonly SsoClientService $ssoClient,
        private readonly UserRepository $userRepository,
    ) {}

    public function check(): bool
    {
        return $this->user() !== null;
    }

    public function guest(): bool
    {
        return ! $this->check();
    }

    public function user(): Authenticatable|null
    {
        if ($this->userResolved) {
            return $this->user;
        }

        $this->userResolved = true;

        $token = $this->extractBearerToken();

        if ($token === null) {
            return null;
        }

        try {
            $sub = $this->parseAndValidate($token);
        } catch (RuntimeException) {
            return null;
        }

        $this->user = $this->userRepository->findByOidcSub($sub);

        return $this->user;
    }

    public function id(): int|string|null
    {
        $user = $this->user();

        if ($user === null) {
            return null;
        }

        $id = $user->getAuthIdentifier();

        return is_int($id) || is_string($id) ? $id : null;
    }

    /** @param array<string, mixed> $credentials */
    public function validate(array $credentials = []): bool
    {
        return false;
    }

    public function hasUser(): bool
    {
        return $this->user !== null;
    }

    public function setUser(Authenticatable $user): static
    {
        $this->user = $user;
        $this->userResolved = true;

        return $this;
    }

    /** @return non-empty-string|null */
    private function extractBearerToken(): string|null
    {
        $header = $this->request->header('Authorization', '');

        if (! str_starts_with($header, 'Bearer ')) {
            return null;
        }

        $token = substr($header, 7);

        return $token !== '' ? $token : null;
    }

    /** @param non-empty-string $rawToken */
    protected function parseAndValidate(string $rawToken): string
    {
        $pem = $this->ssoClient->getIdpPublicKeyPem();

        if ($pem === '') {
            throw new RuntimeException('JWKS public key unavailable.');
        }

        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText('verify-only'),
            InMemory::plainText($pem),
        );

        $issuer = rtrim(config()->string('sso.idp_url'), '/');

        if ($issuer === '') {
            throw new RuntimeException('SSO IDP URL is not configured.');
        }

        $configuration->setValidationConstraints(
            new SignedWith($configuration->signer(), $configuration->verificationKey()),
            new IssuedBy($issuer),
        );

        try {
            $token = $configuration->parser()->parse($rawToken);
        } catch (\Throwable $e) {
            throw new RuntimeException('Malformed access token.', previous: $e);
        }

        if (! $token instanceof Plain) {
            throw new RuntimeException('Encrypted tokens are not supported.');
        }

        try {
            $configuration->validator()->assert($token, ...$configuration->validationConstraints());
        } catch (RequiredConstraintsViolated $e) {
            throw new RuntimeException($e->getMessage());
        }

        if ($token->isExpired(now()->toDateTimeImmutable())) {
            throw new RuntimeException('Access token has expired.');
        }

        /** @var list<string> $products */
        $products = (array) $token->claims()->get('products', []);

        if (! in_array(config()->string('sso.product_slug'), $products, true)) {
            throw new RuntimeException('Token does not grant access to this product.');
        }

        $sub = $token->claims()->get('sub', '');

        if (! is_string($sub) || $sub === '') {
            throw new RuntimeException('Token missing sub claim.');
        }

        return $sub;
    }
}
