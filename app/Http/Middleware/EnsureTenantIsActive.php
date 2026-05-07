<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        if ((bool) (tenant()?->getAttribute('is_suspended') ?? false)) {
            abort(Response::HTTP_FORBIDDEN, 'Tenant is suspended.');
        }

        return $next($request);
    }
}
