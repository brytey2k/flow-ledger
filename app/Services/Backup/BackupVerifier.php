<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Models\Landlord\BackupRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Proves a backup is usable by restoring it into a throwaway database and sanity checking it.
 * Needs the private key, so it runs from a trusted operator machine, not from the app server.
 */
class BackupVerifier
{
    public function __construct(
        private readonly BackupService $backups,
        private readonly BackupEncryptor $encryptor,
    ) {}

    public function verify(BackupRun $run, string $privateKey): void
    {
        $workDir = $this->backups->workDir();
        $token = Str::lower(Str::random(10));
        $encryptedPath = "{$workDir}/verify-{$token}.enc";
        $plainPath = "{$workDir}/verify-{$token}.dump";
        $scratchDatabase = 'restore_verify_' . $token;
        $scratchCreated = false;

        try {
            $this->download($run, $encryptedPath);
            $this->encryptor->decryptFile($encryptedPath, $plainPath, $privateKey);

            DB::statement('CREATE DATABASE "' . $scratchDatabase . '"');
            $scratchCreated = true;

            $this->restoreAndCheck($plainPath, $scratchDatabase, $run->tenant_id !== null);

            $run->update(['verified_at' => now(), 'verify_status' => 'ok', 'verify_error' => null]);
        } catch (Throwable $throwable) {
            $run->update(['verified_at' => now(), 'verify_status' => 'failed', 'verify_error' => mb_substr($throwable->getMessage(), 0, 1000)]);

            throw $throwable;
        } finally {
            if ($scratchCreated) {
                $this->dropDatabase($scratchDatabase);
            }
            @unlink($encryptedPath);
            @unlink($plainPath);
        }
    }

    private function download(BackupRun $run, string $destination): void
    {
        if ($run->path === null) {
            throw new RuntimeException('This backup has no stored file.');
        }

        $source = $this->backups->disk()->readStream($run->path);
        $target = fopen($destination, 'wb');
        if ($source === null || $target === false) {
            throw new RuntimeException('Cannot download the backup file.');
        }

        stream_copy_to_stream($source, $target);
        fclose($target);
        fclose($source);

        if ($run->checksum !== null && hash_file('sha256', $destination) !== $run->checksum) {
            throw new RuntimeException('Checksum mismatch: the stored backup differs from what was uploaded.');
        }
    }

    private function restoreAndCheck(string $plainPath, string $scratchDatabase, bool $isTenant): void
    {
        $connection = $this->backups->centralConnection();
        $restore = Process::timeout($this->backups->intConfig('backup.dump_timeout_seconds'))
            ->env(['PGPASSWORD' => is_string($connection['password'] ?? null) ? $connection['password'] : ''])
            ->run([
                is_string(config('backup.pg_restore_binary')) ? config('backup.pg_restore_binary') : 'pg_restore',
                '--no-owner',
                '--no-privileges',
                '--exit-on-error',
                '--host', is_string($connection['host'] ?? null) ? $connection['host'] : '127.0.0.1',
                '--port', is_scalar($connection['port'] ?? null) ? (string) $connection['port'] : '5432',
                '--username', is_string($connection['username'] ?? null) ? $connection['username'] : '',
                '--dbname', $scratchDatabase,
                $plainPath,
            ]);

        if ($restore->failed()) {
            throw new RuntimeException('pg_restore failed: ' . trim($restore->errorOutput()));
        }

        config(['database.connections.backup_verify' => array_merge($connection, ['database' => $scratchDatabase])]);
        DB::purge('backup_verify');

        $tables = $this->scalarCount("select count(*) from information_schema.tables where table_schema = 'public'");
        if ($tables === 0) {
            throw new RuntimeException('The restored database has no tables.');
        }

        $migrations = $this->scalarCount('select count(*) from migrations');
        if ($migrations === 0) {
            throw new RuntimeException('The restored database has an empty migrations table.');
        }

        if ($isTenant && $this->scalarCount('select count(*) from users') === 0) {
            throw new RuntimeException('The restored tenant database has no users.');
        }
    }

    private function scalarCount(string $sql): int
    {
        $value = DB::connection('backup_verify')->scalar($sql);

        return is_numeric($value) ? (int) $value : 0;
    }

    private function dropDatabase(string $scratchDatabase): void
    {
        DB::purge('backup_verify');

        try {
            DB::statement('DROP DATABASE IF EXISTS "' . $scratchDatabase . '" WITH (FORCE)');
        } catch (Throwable) {
            // Best effort: the name is random and prefixed, so a leftover is easy to spot and drop by hand.
        }
    }
}
