<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Ties the last step of a single sign-in to the browser that began it. The tenant domain keeps a random secret
 * in a cookie that only that browser holds and sends just its hash through the central sign-in, so a hand-off
 * link forwarded to someone else cannot be redeemed by them.
 */
class SsoBinding
{
    /** @return non-empty-string */
    public static function cookieName(Request $request): string
    {
        // The __Host- prefix stops a sibling subdomain from planting or replacing the cookie.
        return $request->isSecure() ? '__Host-sso_bind' : 'sso_bind';
    }

    public static function hash(string $secret): string
    {
        return hash('sha256', $secret);
    }

    public static function isValidHash(string $value): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $value) === 1;
    }
}
