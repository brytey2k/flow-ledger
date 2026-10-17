<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Backup\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('backup:check')]
#[Description('Fail loudly when any database has a missing, stale or altered backup')]
class CheckBackups extends Command
{
    public function handle(BackupService $backups): int
    {
        $problems = $backups->problems();

        if ($problems === []) {
            $this->info('Every database has a fresh, intact backup.');

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->error($problem);
        }

        report(new RuntimeException('Backup check failed: ' . implode(' | ', $problems)));

        return self::FAILURE;
    }
}
