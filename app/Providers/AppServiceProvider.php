<?php

declare(strict_types=1);

namespace App\Providers;

use App\Events\CashbookBalanceChanged;
use App\Listeners\CheckCashBalanceThreshold;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(CashbookBalanceChanged::class, CheckCashBalanceThreshold::class);
    }
}
