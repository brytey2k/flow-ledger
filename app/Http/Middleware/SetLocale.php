<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $sessionLocale = session('locale');
        $user = $request->user();
        $userLocale = $user instanceof \App\Models\Tenant\User ? $user->locale : null;
        $locale = $sessionLocale ?? $userLocale ?? config('app.locale', 'en');

        app()->setLocale(is_string($locale) ? $locale : 'en');

        return $next($request);
    }
}
