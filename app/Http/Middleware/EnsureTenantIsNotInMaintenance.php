<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantAnnouncementService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsNotInMaintenance
{
    public function __construct(private readonly TenantAnnouncementService $announcements) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = tenant();

        if (! $tenant instanceof Tenant || ! $this->announcements->get($tenant)['maintenance_mode']) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        // Landlord administrators arrive through impersonation and must still be able to work.
        if (($request->hasSession() && $request->session()->get('impersonated') === true) || $request->is('impersonate/*')) {
            /** @var Response $response */
            $response = $next($request);

            return $response;
        }

        $message = $this->announcements->get($tenant)['maintenance_message'];

        if ($request->expectsJson()) {
            return response()->json(['message' => $message !== '' ? $message : 'Down for maintenance.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return response()->view('tenant.maintenance', ['message' => $message], Response::HTTP_SERVICE_UNAVAILABLE);
    }
}
