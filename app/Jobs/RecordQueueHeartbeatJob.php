<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\PlatformHeartbeatService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class RecordQueueHeartbeatJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function handle(PlatformHeartbeatService $heartbeat): void
    {
        $heartbeat->recordQueue();
    }
}
