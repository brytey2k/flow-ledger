<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web\Tenant\Auth;

use App\Http\Controllers\Controller;
use App\Support\SsoBinding;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SsoStartController extends Controller
{
    /**
     * Begins single sign-on from the tenant's own domain. A secret is kept in a cookie here, and only its hash
     * travels to the central sign-in, so the finalize step can tell that the browser finishing the sign-in is
     * the one that started it.
     *
     * @param Request $request
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $secret = bin2hex(random_bytes(32));

        $port = $request->getPort();
        $portSuffix = in_array($port, [80, 443], true) ? '' : ":{$port}";
        $query = http_build_query(['return_to' => route('login'), 'bind' => SsoBinding::hash($secret)]);
        $url = $request->getScheme() . '://' . config()->string('app.central_url') . $portSuffix . '/auth/sso/redirect?' . $query;

        return redirect()->away($url)->withCookie(cookie(
            name: SsoBinding::cookieName($request),
            value: $secret,
            minutes: 10,
            path: '/',
            domain: null,
            secure: $request->isSecure(),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        ));
    }
}
