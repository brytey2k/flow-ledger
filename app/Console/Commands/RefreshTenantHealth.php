<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PlatformOverviewService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('health:refresh')]
#[Description('Queue a health reading for every customer and drop readings for customers that no longer exist')]
class RefreshTenantHealth extends Command
{
    public function handle(PlatformOverviewService $overview): int
    {
        $queued = $overview->queueRefreshAll();

        $this->info("Queued a health reading for {$queued} customer(s).");

        return self::SUCCESS;
    }
}
