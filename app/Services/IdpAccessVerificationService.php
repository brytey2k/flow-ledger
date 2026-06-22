<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IdpAccessVerificationService
{
    public function __construct(private readonly SsoClientService $ssoClient) {}

    public function userHasAccess(int|string|null $sub, string $email): bool
    {
        try {
            $token = $this->getClientCredentialsToken();

            $response = $this->ssoClient->idpHttp()
                ->withToken($token)
                ->timeout(10)
                ->connectTimeout(5)
                ->retry(2, 200)
                ->post(
                    rtrim(config()->string('sso.idp_internal_url'), '/') . '/api/m2m/users/verify-access',
                    ['sub' => $sub, 'email' => $email],
                );

            return $response->successful() && (bool) $response->json('has_access', false);
        } catch (\Throwable $e) {
            Log::warning('IdP access verification failed', ['error' => $e->getMessage(), 'email' => $email]);

            return false;
        }
    }

    private function getClientCredentialsToken(): string
    {
        $clientId = config()->string('sso.m2m_client_id');
        $scope = config()->string('sso.m2m_scope', 'login:verify');
        $cacheKey = 'idp.m2m.token.' . sha1($clientId . '|' . $scope);

        return Cache::remember($cacheKey, now()->addMinutes(55), function () use ($clientId, $scope): string {
            $response = $this->ssoClient->idpHttp()
                ->asForm()
                ->timeout(10)
                ->connectTimeout(5)
                ->retry(2, 200)
                ->post(
                    rtrim(config()->string('sso.idp_internal_url'), '/') . '/oauth/token',
                    [
                        'grant_type' => 'client_credentials',
                        'client_id' => $clientId,
                        'client_secret' => config()->string('sso.m2m_client_secret'),
                        'scope' => $scope,
                    ],
                );

            if ($response->failed()) {
                throw new RuntimeException('Failed to obtain M2M token from IdP: ' . $response->body());
            }

            $token = $response->json('access_token');

            if (! is_string($token) || $token === '') {
                throw new RuntimeException('IdP returned an empty M2M access token.');
            }

            return $token;
        });
    }
}
