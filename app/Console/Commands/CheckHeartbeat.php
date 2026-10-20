<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\PlatformHeartbeatService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('heartbeat:check')]
#[Description('Report an error when the queue workers have stopped while the scheduler is still running')]
class CheckHeartbeat extends Command
{
    public function handle(PlatformHeartbeatService $heartbeat): int
    {
        $status = $heartbeat->status();

        if ($status['queue']['ok'] || ! $status['scheduler']['ok']) {
            // A stopped scheduler cannot report itself, so it is caught by the external uptime monitor.
            $this->info($status['queue']['ok'] ? 'Scheduler and queue workers are running.' : 'The scheduler is not running; the external monitor raises that alert.');

            return self::SUCCESS;
        }

        $this->error('The queue workers have stopped processing jobs.');
        report(new RuntimeException('Queue workers have not processed the heartbeat job recently.'));

        return self::FAILURE;
    }
}
