<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\RecordQueueHeartbeatJob;
use App\Services\PlatformHeartbeatService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('heartbeat:record')]
#[Description('Record that the scheduler is running and queue a job that records that the queue workers are running')]
class RecordHeartbeat extends Command
{
    public function handle(PlatformHeartbeatService $heartbeat): int
    {
        $heartbeat->recordScheduler();
        RecordQueueHeartbeatJob::dispatch();

        return self::SUCCESS;
    }
}
