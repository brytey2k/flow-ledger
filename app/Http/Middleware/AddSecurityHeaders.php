<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AddSecurityHeaders
{
    private const HSTS = 'max-age=31536000; includeSubDomains';

    private const PERMISSIONS_POLICY = 'camera=(), microphone=(), geolocation=(), payment=(), usb=()';

    /**
     * Directives that harden framing, plugins and form targets without restricting script or
     * style sources, since the UI relies on inline scripts and third-party font hosting.
     */
    private const CONTENT_SECURITY_POLICY = "frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'";

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => self::PERMISSIONS_POLICY,
            'Content-Security-Policy' => self::CONTENT_SECURITY_POLICY,
        ];

        if ($request->isSecure()) {
            $headers['Strict-Transport-Security'] = self::HSTS;
        }

        foreach ($headers as $name => $value) {
            if (! $response->headers->has($name)) {
                $response->headers->set($name, $value);
            }
        }

        return $response;
    }
}
