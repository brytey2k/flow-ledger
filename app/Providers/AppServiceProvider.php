<?php

declare(strict_types=1);

namespace App\Providers;

use App\Auth\IamJwtGuard;
use App\Events\CashbookBalanceChanged;
use App\Interfaces\SessionInvalidatorInterface;
use App\Listeners\CheckCashBalanceThreshold;
use App\Repositories\UserRepository;
use App\Services\SessionInvalidatorService;
use App\Services\SsoClientService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SessionInvalidatorInterface::class, SessionInvalidatorService::class);
    }

    public function boot(): void
    {
        Event::listen(CashbookBalanceChanged::class, CheckCashBalanceThreshold::class);

        Auth::extend('iam_jwt', fn(Application $app, string $name, array $config) => new IamJwtGuard(
            Auth::createUserProvider(is_string($config['provider'] ?? null) ? $config['provider'] : null),
            $app->make(Request::class),
            $app->make(SsoClientService::class),
            $app->make(UserRepository::class),
        ));
    }
}
