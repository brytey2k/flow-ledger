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
        $currentTenant = tenant();
        if ($currentTenant instanceof \App\Models\Tenant && (bool) $currentTenant->getAttribute('is_suspended')) {
            abort(Response::HTTP_FORBIDDEN, 'Tenant is suspended.');
        }

        /** @var Response $response */
        $response = $next($request);

        return $response;
    }
}
