<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Data\Auth\SsoUserClaimsDto;
use App\Exceptions\UnverifiedEmailException;
use App\Http\Controllers\Controller;
use App\Models\Domain;
use App\Models\Tenant;
use App\Services\SsoClientService;
use App\Services\SsoUserProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class SsoController extends Controller
{
    public function __construct(
        private readonly SsoClientService $ssoClient,
        private readonly SsoUserProvisioningService $provisioner,
    ) {}

    /**
     * Step 1 — Generate PKCE + state, then redirect the user to the IDP.
     * Runs on the central domain; no tenant context required.
     *
     * @param Request $request
     */
    public function redirect(Request $request): RedirectResponse
    {
        $pkce = $this->ssoClient->generatePkce();
        $state = $this->ssoClient->generateState();

        Cache::put("sso_pkce:{$state}", $pkce['verifier'], now()->addMinutes(5));

        $returnTo = (string) $request->query('return_to', '');
        if ($returnTo !== '' && $this->isAllowedReturnUrl($returnTo)) {
            Cache::put("sso_return:{$state}", $returnTo, now()->addMinutes(5));
        }

        return redirect()->away(
            $this->ssoClient->buildAuthorizationUrl($state, $pkce['challenge']),
        )->withCookie(cookie(
            name: 'sso_state',
            value: $state,
            minutes: 5,
            secure: parse_url(config()->string('sso.redirect_uri'), PHP_URL_SCHEME) === 'https',
            sameSite: 'lax',
        ));
    }

    /**
     * Step 2 — IDP returns here with ?code=&state=
     * Validates the callback, exchanges the code, and routes the user.
     *
     * @param Request $request
     */
    public function callback(Request $request): RedirectResponse
    {
        $state = (string) $request->query('state', '');
        $code = (string) $request->query('code', '');

        if ($state === '' || $code === '') {
            return $this->failRedirect('SSO callback is missing required parameters.');
        }

        if (! $this->ssoClient->validateAndConsumeState($state)) {
            return $this->failRedirect('Invalid or expired SSO state. Please try again.');
        }

        if ($request->cookie('sso_state') !== $state) {
            return $this->failRedirect('This sign-in attempt could not be verified. Please try again.');
        }

        $pulledReturn = Cache::pull("sso_return:{$state}");
        $returnTo = is_string($pulledReturn) ? $pulledReturn : '';

        $pulled = Cache::pull("sso_pkce:{$state}");
        $verifier = is_string($pulled) ? $pulled : '';
        if ($verifier === '') {
            return $this->failRedirect('PKCE code verifier is missing. Please try again.', $returnTo);
        }

        try {
            $tokens = $this->ssoClient->exchangeCodeForTokens($code, $verifier);
        } catch (RuntimeException $e) {
            Log::warning('SSO token exchange failed', ['error' => $e->getMessage()]);

            return $this->failRedirect('Could not complete sign-in. Please try again.', $returnTo);
        }

        if (! empty($tokens['id_token'])) {
            try {
                $this->ssoClient->validateIdToken($tokens['id_token']);
            } catch (RuntimeException $e) {
                Log::warning('SSO ID token validation failed', ['error' => $e->getMessage()]);

                return $this->failRedirect('Identity token validation failed. Please try again.', $returnTo);
            }
        }

        try {
            $claims = $this->ssoClient->fetchUserInfo($tokens['access_token']);
        } catch (RuntimeException $e) {
            Log::warning('SSO userinfo fetch failed', ['error' => $e->getMessage()]);

            return $this->failRedirect('Could not retrieve your account information from the identity provider.', $returnTo);
        }

        if ($claims->isLandlordUser()) {
            try {
                return $this->loginAsLandlord($request, $claims);
            } catch (UnverifiedEmailException $e) {
                return $this->failRedirect($e->getMessage(), $returnTo);
            }
        }

        if (! $claims->hasProductAccess(config()->string('sso.product_slug'))) {
            return $this->failRedirect('Your account does not have access to this application.', $returnTo);
        }

        return $this->routeToTenant($claims);
    }

    /**
     * Clear the current session and redirect to the IDP end-session endpoint.
     *
     * @param Request $request
     */
    public function logout(Request $request): RedirectResponse
    {
        $guard = Auth::guard('landlord')->check() ? 'landlord' : 'web';
        Auth::guard($guard)->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->away(
            rtrim(config()->string('sso.idp_url'), '/') . '/connect/end-session',
        );
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function loginAsLandlord(Request $request, SsoUserClaimsDto $claims): RedirectResponse
    {
        $user = $this->provisioner->findOrCreateLandlordUser($claims);

        Auth::guard('landlord')->login($user);
        $request->session()->regenerate();

        return redirect()->route('landlord.tenants.index');
    }

    private function routeToTenant(SsoUserClaimsDto $claims): RedirectResponse
    {
        $tenant = Tenant::where('idp_tenant_id', $claims->tenant_id)->first();

        if ($tenant === null) {
            return $this->failRedirect('Your organisation is not registered in this application.');
        }

        $domain = $tenant->domains()->first()?->domain;

        if ($domain === null) {
            return $this->failRedirect('Your organisation does not have a configured domain.');
        }

        $loginToken = Str::random(64);
        // Explicit store name bypasses Stancl's tenant cache tagging, since this
        // token is written on the central domain but read on a tenant subdomain
        // where InitializeTenancyByDomain has already tagged the Cache facade.
        // The tenant id is baked into the key itself so the token can only ever
        // resolve against the tenant it was minted for.
        Cache::store(config()->string('cache.default'))
            ->put("sso_login:{$tenant->id}:{$loginToken}", $claims->toArray(), now()->addSeconds(30));

        $port = request()->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ":{$port}";
        $scheme = request()->isSecure() ? 'https' : 'http';

        return redirect()->away("{$scheme}://{$domain}{$portSuffix}/auth/sso/finalize?token={$loginToken}");
    }

    private function isAllowedReturnUrl(string $url): bool
    {
        // Browsers normalise backslashes to forward slashes before navigating,
        // but parse_url() does not — so "/\evil.com" parses as a safe host-less
        // path here while the browser actually follows it as "//evil.com".
        if (str_contains($url, '\\')) {
            return false;
        }

        $parsed = parse_url($url);

        if ($parsed === false) {
            return false;
        }

        if (empty($parsed['host'])) {
            return isset($parsed['path']);
        }

        $host = $parsed['host'];
        $centralHost = parse_url(config()->string('app.url'), PHP_URL_HOST) ?? '';

        if ($host === $centralHost || str_ends_with($host, '.' . $centralHost)) {
            return true;
        }

        return Domain::where('domain', $host)->exists();
    }

    private function failRedirect(string $message, string $returnTo = ''): RedirectResponse
    {
        if ($returnTo !== '') {
            $separator = str_contains($returnTo, '?') ? '&' : '?';

            return redirect()->away($returnTo . $separator . 'sso_error=' . urlencode($message));
        }

        abort(403, $message);
    }
}
