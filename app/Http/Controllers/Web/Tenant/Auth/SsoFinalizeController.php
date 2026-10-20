<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant\Auth;

use App\Data\Auth\SsoUserClaimsDto;
use App\Exceptions\UnverifiedEmailException;
use App\Http\Controllers\Controller;
use App\Interfaces\SessionInvalidatorInterface;
use App\Models\Tenant;
use App\Services\SsoUserProvisioningService;
use App\Support\SsoBinding;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SsoFinalizeController extends Controller
{
    public function __construct(
        private readonly SsoUserProvisioningService $provisioner,
        private readonly SessionInvalidatorInterface $sessionInvalidator,
    ) {}

    /**
     * Step 3 - Completes SSO login on the tenant subdomain.
     * The central-domain callback placed a one-time token in cache. When this browser is the one that began the
     * sign-in (it holds the secret whose hash travelled with the token) the login completes at once. Otherwise
     * the person is shown who they are about to be signed in as and must confirm, so a hand-off link forwarded
     * to someone else cannot silently sign them in as a stranger.
     *
     * @param Request $request
     */
    public function __invoke(Request $request): RedirectResponse|View
    {
        $token = $this->tokenFrom($request);
        $raw = $this->peek($token);
        $claims = $this->claimsFor($raw);

        if ($this->isBoundToThisBrowser($request, $raw)) {
            return $this->complete($request, $token);
        }

        return view('tenant.auth.sso-confirm', ['token' => $token, 'name' => $claims->name, 'email' => $claims->email]);
    }

    /** The person confirmed who they are signing in as. */
    public function confirm(Request $request): RedirectResponse
    {
        $token = $this->tokenFrom($request, 'input');
        $this->claimsFor($this->peek($token));

        return $this->complete($request, $token);
    }

    private function tokenFrom(Request $request, string $source = 'query'): string
    {
        $raw = $source === 'input' ? $request->input('token', '') : $request->query('token', '');
        $token = is_string($raw) ? $raw : '';

        if ($token === '') {
            abort(403, 'Missing SSO login token.');
        }

        return $token;
    }

    /** @return array<string, mixed> */
    private function peek(string $token): array
    {
        $currentTenant = tenant();
        abort_unless($currentTenant instanceof Tenant && is_scalar($currentTenant->getKey()), 403);

        /** @var array<string, mixed>|null $raw */
        $raw = Cache::store(config()->string('cache.default'))->get("sso_login:{$currentTenant->getKey()}:{$token}");

        if ($raw === null) {
            abort(403, 'SSO login token is invalid or has expired. Please sign in again.');
        }

        return $raw;
    }

    /** @param array<string, mixed> $raw */
    private function claimsFor(array $raw): SsoUserClaimsDto
    {
        $currentTenant = tenant();
        abort_unless($currentTenant instanceof Tenant, 403);

        $claims = SsoUserClaimsDto::fromArray($raw);

        // Defensive check: the tenant-scoped cache key already guarantees this,
        // but assert the IDP's tenant claim matches this tenant in case the key
        // scoping is ever bypassed or misconfigured.
        if ($claims->tenant_id === null || ! is_scalar($currentTenant->idp_tenant_id) || (string) $currentTenant->idp_tenant_id !== $claims->tenant_id) {
            abort(403, 'SSO login token is not valid for this organisation.');
        }

        return $claims;
    }

    /** @param array<string, mixed> $raw */
    private function isBoundToThisBrowser(Request $request, array $raw): bool
    {
        $expected = $raw['bind'] ?? null;
        $secret = $request->cookie(SsoBinding::cookieName($request));

        return is_string($expected) && is_string($secret) && $secret !== '' && hash_equals($expected, SsoBinding::hash($secret));
    }

    private function complete(Request $request, string $token): RedirectResponse
    {
        $currentTenant = tenant();
        abort_unless($currentTenant instanceof Tenant && is_scalar($currentTenant->getKey()), 403);

        // The token is one-time: whoever pulls it first wins.
        /** @var array<string, mixed>|null $raw */
        $raw = Cache::store(config()->string('cache.default'))->pull("sso_login:{$currentTenant->getKey()}:{$token}");

        if ($raw === null) {
            abort(403, 'SSO login token is invalid or has expired. Please sign in again.');
        }

        $claims = $this->claimsFor($raw);

        try {
            $user = $this->provisioner->findOrCreateTenantUser($claims);
        } catch (UnverifiedEmailException $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $this->sessionInvalidator->track($user->id, $request->session()->getId());

        return redirect()->intended(route('dashboard'))->withoutCookie(SsoBinding::cookieName($request));
    }
}
