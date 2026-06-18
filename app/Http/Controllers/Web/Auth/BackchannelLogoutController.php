<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\Tenant\User;
use App\Services\SsoClientService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Plain;
use Lcobucci\JWT\Validation\Constraint\IssuedBy;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\RequiredConstraintsViolated;
use RuntimeException;

class BackchannelLogoutController extends Controller
{
    public function __construct(
        private readonly SsoClientService $ssoClient,
    ) {}

    public function __invoke(Request $request): Response
    {
        $rawToken = $request->input('logout_token', '');

        if (! is_string($rawToken) || $rawToken === '') {
            return response('Missing logout_token', 400);
        }

        try {
            [$sub, $tid] = $this->parseAndValidate($rawToken);
        } catch (RuntimeException $e) {
            Log::warning('Back-channel logout: token validation failed', ['error' => $e->getMessage()]);

            return response('Invalid logout token', 400);
        }

        /** @var Tenant|null $tenant */
        $tenant = Tenant::query()->where('idp_tenant_id', $tid)->first();

        if ($tenant === null) {
            Log::info('Back-channel logout: tenant not found, ignoring', ['tid' => $tid]);

            return response('', 200);
        }

        tenancy()->initialize($tenant);

        try {
            $user = User::query()->where('oidc_sub', $sub)->first();

            if ($user === null) {
                Log::info('Back-channel logout: user not found in tenant, ignoring', [
                    'tid' => $tid,
                    'sub' => $sub,
                ]);
            } else {
                DB::table('sessions')->where('user_id', $user->id)->delete();

                Log::info('Back-channel logout: user sessions revoked', [
                    'tid' => $tid,
                    'sub' => $sub,
                    'user_id' => $user->id,
                ]);
            }
        } finally {
            tenancy()->end();
        }

        return response('', 200);
    }

    /** @return array{0: string, 1: string} */
    private function parseAndValidate(string $rawToken): array
    {
        $pem = $this->ssoClient->getIdpPublicKeyPem();

        if ($pem === '') {
            throw new RuntimeException('Missing logout token verification material.');
        }

        $configuration = Configuration::forAsymmetricSigner(
            new Sha256(),
            InMemory::plainText('verify-only'),
            InMemory::plainText($pem),
        );

        $issuer = rtrim(config()->string('sso.idp_url'), '/');

        if ($issuer === '') {
            throw new RuntimeException('Missing logout token issuer.');
        }

        $configuration->setValidationConstraints(
            new SignedWith($configuration->signer(), $configuration->verificationKey()),
            new IssuedBy($issuer),
        );

        if ($rawToken === '') {
            throw new RuntimeException('Logout token is empty.');
        }

        $token = $configuration->parser()->parse($rawToken);

        if (! $token instanceof Plain) {
            throw new RuntimeException('Encrypted logout tokens are not supported.');
        }

        try {
            $configuration->validator()->assert($token, ...$configuration->validationConstraints());
        } catch (RequiredConstraintsViolated $e) {
            throw new RuntimeException($e->getMessage());
        }

        if ($token->isExpired(now()->toDateTimeImmutable())) {
            throw new RuntimeException('Logout token expired.');
        }

        /** @var array<string, mixed>|null $events */
        $events = $token->claims()->get('events');

        if (! is_array($events) || ! array_key_exists('http://schemas.openid.net/event/backchannel-logout', $events)) {
            throw new RuntimeException('Missing backchannel-logout event claim.');
        }

        $sub = $token->claims()->get('sub', '');
        $tid = $token->claims()->get('tid', '');

        if (! is_string($sub) || $sub === '' || ! is_string($tid) || $tid === '') {
            throw new RuntimeException('Missing sub or tid claim.');
        }

        return [$sub, $tid];
    }
}
