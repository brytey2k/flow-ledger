<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Features\DelegateIdentityToIdp;
use App\Models\Tenant\User;
use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

class ForcePasswordChange
{
    /**
     * @param Request $request
     * @param Closure(Request): (Response) $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (
            $user instanceof User
            && $user->must_change_password
            && ! $request->routeIs('password.change', 'password.change.update', 'logout')
            && ! Feature::for(tenant())->active(DelegateIdentityToIdp::class)
        ) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
