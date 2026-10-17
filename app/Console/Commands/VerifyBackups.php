<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Landlord\BackupRun;
use App\Services\Backup\BackupService;
use App\Services\Backup\BackupVerifier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('backup:verify {--private-key-file= : File containing the private backup key} {--database= : Only verify this database}')]
#[Description('Restore the newest backup of each database into a scratch database and sanity check it')]
class VerifyBackups extends Command
{
    public function handle(BackupService $backups, BackupVerifier $verifier): int
    {
        $keyFile = $this->option('private-key-file');
        if (! is_string($keyFile) || ! is_file($keyFile)) {
            $this->error('Pass --private-key-file=/path/to/key (run this from a trusted machine, not the app server).');

            return self::FAILURE;
        }
        $privateKey = trim((string) file_get_contents($keyFile));

        $only = $this->option('database');
        $failed = false;

        foreach ($backups->databases() as $database) {
            if (is_string($only) && $only !== '' && $only !== $database['name']) {
                continue;
            }

            $run = BackupRun::query()->usable()->where('database_name', $database['name'])->latest('finished_at')->first();
            if ($run === null) {
                $this->error("{$database['name']}: no backup to verify.");
                $failed = true;

                continue;
            }

            try {
                $verifier->verify($run, $privateKey);
                $this->info("{$database['name']}: restored and verified ({$run->path}).");
            } catch (Throwable $throwable) {
                $this->error("{$database['name']}: " . $throwable->getMessage());
                $failed = true;
            }
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
