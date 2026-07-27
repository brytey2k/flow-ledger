<?php

declare(strict_types=1);

use App\Console\Commands\SendRetirementReminders;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // nginx never sets X-Forwarded-Host, so it's excluded here: trusting it would let a
        // client spoof Request::getHost(), which both URL generation and
        // InitializeTenancyByDomain rely on to resolve the tenant.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR
                | Request::HEADER_X_FORWARDED_PORT
                | Request::HEADER_X_FORWARDED_PROTO,
        );
        $middleware->validateCsrfTokens(except: [
            'auth/sso/backchannel-logout',
        ]);
        $middleware->web(append: [
            App\Http\Middleware\SetLocale::class,
        ]);
        $middleware->alias([
            'tenant.active' => App\Http\Middleware\EnsureTenantIsActive::class,
            'force.password.change' => App\Http\Middleware\ForcePasswordChange::class,
            'check.force.logout' => App\Http\Middleware\CheckForceLogout::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SendRetirementReminders::class)->dailyAt('08:00');
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->dontReport([
            Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException::class,
        ]);
    })->create();
