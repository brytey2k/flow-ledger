<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\BackupStatus;
use App\Services\Backup\BackupService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use RuntimeException;

#[Signature('backup:run {--no-prune : Keep expired backups instead of deleting them}')]
#[Description('Dump, encrypt and upload the central database and every tenant database')]
class RunBackups extends Command
{
    public function handle(BackupService $backups): int
    {
        try {
            $backups->preflight();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());
            report($exception);

            return self::FAILURE;
        }

        $runs = $backups->runAll();

        $this->table(
            ['Database', 'Status', 'Size', 'Detail'],
            $runs->map(fn($run): array => [
                $run->database_name,
                $run->status->value,
                $run->size_bytes !== null ? number_format($run->size_bytes / 1048576, 2) . ' MB' : '-',
                $run->error ?? '',
            ])->all(),
        );

        if (! $this->option('no-prune')) {
            $this->info('Pruned ' . $backups->prune() . ' expired backup(s).');
        }

        return $runs->contains(fn($run): bool => $run->status === BackupStatus::Failed) ? self::FAILURE : self::SUCCESS;
    }
}
