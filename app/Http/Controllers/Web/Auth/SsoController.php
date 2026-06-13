<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Auth;

use App\Data\Auth\SsoUserClaimsDto;
use App\Exceptions\UnverifiedEmailException;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\SsoClientService;
use App\Services\SsoUserProvisioningService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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

        $request->session()->put("sso_pkce:{$state}", $pkce['verifier']);

        $returnTo = (string) $request->query('return_to', '');
        if ($returnTo !== '') {
            $request->session()->put("sso_return:{$state}", $returnTo);
        }

        return redirect()->away(
            $this->ssoClient->buildAuthorizationUrl($state, $pkce['challenge']),
        );
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

        $pulledReturn = $request->session()->pull("sso_return:{$state}");
        $returnTo = is_string($pulledReturn) ? $pulledReturn : '';

        $pulled = $request->session()->pull("sso_pkce:{$state}");
        $verifier = is_string($pulled) ? $pulled : '';
        if ($verifier === '') {
            return $this->failRedirect('PKCE code verifier is missing. Please try again.', $returnTo);
        }

        try {
            $tokens = $this->ssoClient->exchangeCodeForTokens($code, $verifier);
        } catch (RuntimeException $e) {
            return $this->failRedirect('Could not complete sign-in. Please try again.', $returnTo);
        }

        if (! empty($tokens['id_token'])) {
            try {
                $this->ssoClient->validateIdToken($tokens['id_token']);
            } catch (RuntimeException $e) {
                return $this->failRedirect('Identity token validation failed. Please try again.', $returnTo);
            }
        }

        try {
            $claims = $this->ssoClient->fetchUserInfo($tokens['access_token']);
        } catch (RuntimeException $e) {
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
        Cache::put("sso_login:{$loginToken}", $claims->toArray(), now()->addSeconds(30));

        $port = parse_url(config()->string('app.url'), PHP_URL_PORT);
        $portSuffix = $port ? ":{$port}" : '';
        $scheme = request()->isSecure() ? 'https' : 'http';

        return redirect()->away("{$scheme}://{$domain}{$portSuffix}/auth/sso/finalize?token={$loginToken}");
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
