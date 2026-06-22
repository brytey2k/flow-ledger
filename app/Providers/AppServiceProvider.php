<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CashbookBalanceChanged;
use App\Interfaces\SessionInvalidatorInterface;
use App\Listeners\CheckCashBalanceThreshold;
use App\Services\SessionInvalidatorService;
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
    }
}
