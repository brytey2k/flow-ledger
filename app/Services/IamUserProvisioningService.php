<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Tenant\User as TenantUser;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class IamUserProvisioningService
{
    private const int TOKEN_REQUEST_TIMEOUT = 10;

    private const int INVITE_REQUEST_TIMEOUT = 10;

    private const int CONNECT_TIMEOUT = 5;

    private const int TOKEN_EXPIRY_BUFFER_SECONDS = 60;

    private function cacheKey(): string
    {
        $scope = config()->string('sso.m2m_invite_scope');

        return 'idp.m2m.token.' . sha1(config()->string('sso.m2m_client_id') . '|' . $scope);
    }

    /**
     * Invite a tenant user in the IDP: creates (or attaches an existing)
     * identity and triggers the IDP's invite/verification email.
     *
     * @param TenantUser $user
     * @param string $idpTenantId
     *
     * @throws RuntimeException when the token or invite request fails
     *
     * @return array{iam_user_id: string, status: 'created'|'exists'}
     */
    public function invite(TenantUser $user, string $idpTenantId): array
    {
        $token = $this->getClientCredentialsToken();

        $response = $this->idpHttp()->withToken($token)
            ->acceptJson()
            ->timeout(self::INVITE_REQUEST_TIMEOUT)
            ->connectTimeout(self::CONNECT_TIMEOUT)
            ->retry(2, 200, throw: false)
            ->post($this->inviteUrl(), [
                'product_slug' => config()->string('sso.product_slug'),
                'idp_tenant_id' => $idpTenantId,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('IDP user invite failed: ' . $response->body());
        }

        $json = $response->json();

        if (! is_array($json) || ! isset($json['user_id']) || (! is_int($json['user_id']) && ! is_string($json['user_id']))) {
            throw new RuntimeException('IDP user invite response was invalid.');
        }

        $idpUserId = (string) $json['user_id'];

        $status = $json['status'] ?? 'created';
        /** @var 'created'|'exists' $status */
        $status = in_array($status, ['created', 'exists'], true) ? $status : 'created';

        Log::info('User invite sent to IDP', [
            'user_id' => $user->id,
            'idp_user_id' => $idpUserId,
            'status' => $status,
        ]);

        return ['iam_user_id' => $idpUserId, 'status' => $status];
    }

    private function getClientCredentialsToken(): string
    {
        $cachedToken = Cache::get($this->cacheKey());

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        try {
            $response = $this->idpHttp()->asForm()
                ->acceptJson()
                ->timeout(self::TOKEN_REQUEST_TIMEOUT)
                ->connectTimeout(self::CONNECT_TIMEOUT)
                ->retry(2, 200)
                ->post($this->tokenUrl(), [
                    'grant_type' => 'client_credentials',
                    'client_id' => config()->string('sso.m2m_client_id'),
                    'client_secret' => config()->string('sso.m2m_client_secret'),
                    'scope' => config()->string('sso.m2m_invite_scope'),
                ]);
        } catch (RequestException $exception) {
            throw new RuntimeException('IDP client token request failed: ' . $exception->getMessage(), previous: $exception);
        }

        if ($response->failed()) {
            throw new RuntimeException('IDP client token request failed: ' . $response->body());
        }

        $json = $response->json();

        if (! is_array($json)) {
            throw new RuntimeException('IDP client token response was invalid.');
        }

        $accessToken = $json['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('IDP client token response did not include an access token.');
        }

        $expiresInRaw = $json['expires_in'] ?? 300;
        /** @phpstan-ignore-next-line argument.type */
        $expiresIn = is_int($expiresInRaw) ? $expiresInRaw : intval($expiresInRaw);

        $ttl = max($expiresIn - self::TOKEN_EXPIRY_BUFFER_SECONDS, self::TOKEN_EXPIRY_BUFFER_SECONDS);
        Cache::put($this->cacheKey(), $accessToken, now()->addSeconds($ttl));

        return $accessToken;
    }

    private function tokenUrl(): string
    {
        return rtrim(config()->string('sso.idp_internal_url'), '/') . '/oauth/token';
    }

    private function inviteUrl(): string
    {
        return rtrim(config()->string('sso.idp_internal_url'), '/') . '/api/m2m/tenants/users/invite';
    }

    private function idpHttp(): \Illuminate\Http\Client\PendingRequest
    {
        return config()->boolean('sso.verify_ssl', true)
            ? Http::withOptions([])
            : Http::withoutVerifying();
    }
}
