<?php

declare(strict_types=1);

use App\Console\Commands\CheckBackups;
use App\Console\Commands\CheckHeartbeat;
use App\Console\Commands\RecordHeartbeat;
use App\Console\Commands\RefreshTenantHealth;
use App\Console\Commands\RunBackups;
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
        $middleware->redirectGuestsTo(static fn(Request $request): string => $request->routeIs('landlord.*')
            ? route('landlord.login')
            : route('login'));
        $middleware->validateCsrfTokens(except: [
            'auth/sso/backchannel-logout',
        ]);
        $middleware->web(append: [
            App\Http\Middleware\SetLocale::class,
            App\Http\Middleware\AddSecurityHeaders::class,
        ]);
        $middleware->api(append: [
            App\Http\Middleware\AddSecurityHeaders::class,
        ]);
        $middleware->alias([
            'tenant.active' => App\Http\Middleware\EnsureTenantIsActive::class,
            'tenant.maintenance' => App\Http\Middleware\EnsureTenantIsNotInMaintenance::class,
            'check.force.logout' => App\Http\Middleware\CheckForceLogout::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command(SendRetirementReminders::class)->dailyAt('08:00');
        $schedule->command(RunBackups::class)->everySixHours()->withoutOverlapping(120);
        $schedule->command(CheckBackups::class)->hourly();
        $schedule->command(RefreshTenantHealth::class)->everyTenMinutes()->withoutOverlapping(30);
        $schedule->command(RecordHeartbeat::class)->everyMinute();
        $schedule->command(CheckHeartbeat::class)->everyFiveMinutes();
    })
    ->withExceptions(static function (Exceptions $exceptions): void {
        Integration::handles($exceptions);

        $exceptions->dontReport([
            Stancl\Tenancy\Exceptions\TenantCouldNotBeIdentifiedOnDomainException::class,
        ]);
    })->create();
