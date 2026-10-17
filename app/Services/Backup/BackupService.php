<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Enums\BackupStatus;
use App\Models\Landlord\BackupRun;
use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class BackupService
{
    public function __construct(private readonly BackupEncryptor $encryptor) {}

    /**
     * Fails fast with a readable reason when backups cannot work on this host.
     *
     * @throws RuntimeException
     */
    public function preflight(): void
    {
        $publicKey = config('backup.public_key');
        if (! is_string($publicKey) || $publicKey === '') {
            throw new RuntimeException('BACKUP_ENCRYPTION_PUBLIC_KEY is not set. Run `php artisan backup:generate-key`.');
        }

        $plain = $this->workDir() . '/preflight.txt';
        try {
            file_put_contents($plain, 'flow-ledger backup preflight');
            $this->encryptor->encryptFile($plain, $plain . '.enc', $publicKey);
        } finally {
            @unlink($plain);
            @unlink($plain . '.enc');
        }

        $diskConfig = config('filesystems.disks.' . $this->diskName());
        if (! is_array($diskConfig)) {
            throw new RuntimeException('The backup disk is not defined in config/filesystems.php.');
        }
        if (($diskConfig['driver'] ?? null) === 's3' && ! (is_string($diskConfig['bucket'] ?? null) && $diskConfig['bucket'] !== '')) {
            throw new RuntimeException('The backup disk has no bucket configured (BACKUP_DISK_BUCKET).');
        }

        $result = Process::run([$this->binary('pg_dump_binary'), '--version']);
        if ($result->failed()) {
            throw new RuntimeException('pg_dump is not installed or not on the PATH. Install the PostgreSQL client tools matching the server version.');
        }

        $this->assertClientMatchesServer($result->output());
    }

    /**
     * A dump made by a newer pg_dump than the server may not restore on that server (for example
     * PostgreSQL 17+ adds settings that 16 rejects), which is the worst moment to find out.
     *
     * @param string $pgDumpVersionOutput
     */
    private function assertClientMatchesServer(string $pgDumpVersionOutput): void
    {
        if ((bool) config('backup.allow_client_version_mismatch')) {
            return;
        }

        if (preg_match('/(\d+)(?:\.\d+)*/', $pgDumpVersionOutput, $matches) !== 1) {
            throw new RuntimeException('Could not read the pg_dump version.');
        }

        $clientMajor = (int) $matches[1];
        $serverVersion = DB::selectOne('show server_version_num');
        $serverNumber = is_object($serverVersion) ? ($serverVersion->server_version_num ?? null) : null;
        $serverMajor = is_numeric($serverNumber) ? intdiv((int) $serverNumber, 10000) : 0;

        if ($serverMajor !== 0 && $clientMajor !== $serverMajor) {
            throw new RuntimeException("pg_dump is version {$clientMajor} but the database server is version {$serverMajor}. Install postgresql-client-{$serverMajor} so backups restore cleanly (or set BACKUP_PG_DUMP_BINARY to it).");
        }
    }

    /**
     * @return Collection<int, array{name: string, tenant_id: string|null}>
     */
    public function databases(): Collection
    {
        /** @var Collection<int, array{name: string, tenant_id: string|null}> $databases */
        $databases = collect();
        $databases->push(['name' => $this->centralDatabaseName(), 'tenant_id' => null]);

        foreach (Tenant::query()->orderBy('id')->get() as $tenant) {
            assert($tenant instanceof Tenant);
            $databases->push(['name' => $this->stringOption($tenant->database()->getName()), 'tenant_id' => $tenant->tenantId()]);
        }

        return $databases;
    }

    /**
     * @return Collection<int, BackupRun>
     */
    public function runAll(): Collection
    {
        return $this->databases()->map(fn(array $database): BackupRun => $this->backup($database['name'], $database['tenant_id']));
    }

    public function backup(string $databaseName, string|null $tenantId): BackupRun
    {
        $run = BackupRun::create([
            'database_name' => $databaseName,
            'tenant_id' => $tenantId,
            'status' => BackupStatus::Running,
            'started_at' => now(),
            'is_monthly' => ! BackupRun::query()->usable()
                ->where('database_name', $databaseName)
                ->where('finished_at', '>=', now()->startOfMonth())
                ->exists(),
        ]);

        $workDir = $this->workDir();
        $plainPath = $workDir . '/' . $databaseName . '.dump';
        $encryptedPath = $plainPath . '.enc';

        try {
            $this->dump($databaseName, $plainPath);
            $this->encryptor->encryptFile($plainPath, $encryptedPath, $this->publicKey());

            $size = (int) filesize($encryptedPath);
            $remotePath = sprintf('%s/%s/%s-%s.dump.enc', now()->format('Y-m'), $databaseName, $databaseName, now()->format('Ymd-His'));

            $stream = fopen($encryptedPath, 'rb');
            if ($stream === false) {
                throw new RuntimeException('Cannot read the encrypted backup for upload.');
            }
            $this->disk()->writeStream($remotePath, $stream);
            if (is_resource($stream)) {
                fclose($stream);
            }

            if ($this->disk()->size($remotePath) !== $size) {
                throw new RuntimeException('Uploaded backup size does not match the local file.');
            }

            $run->update([
                'status' => BackupStatus::Completed,
                'path' => $remotePath,
                'size_bytes' => $size,
                'checksum' => hash_file('sha256', $encryptedPath),
                'finished_at' => now(),
            ]);
        } catch (Throwable $throwable) {
            $run->update([
                'status' => BackupStatus::Failed,
                'error' => mb_substr($throwable->getMessage(), 0, 1000),
                'finished_at' => now(),
            ]);
            report($throwable);
        } finally {
            @unlink($plainPath);
            @unlink($encryptedPath);
        }

        return $run->refresh();
    }

    /**
     * Deletes backups past their retention. The newest good backup of every database is never
     * removed, so a long outage cannot leave a database with no restore point.
     *
     * @return int number of backups removed
     */
    public function prune(): int
    {
        $dailyCutoff = now()->subDays($this->intConfig('backup.retention_days'));
        $monthlyCutoff = now()->subMonths($this->intConfig('backup.monthly_retention_months'));

        $newestIds = BackupRun::query()->usable()
            ->selectRaw('max(id) as id')
            ->groupBy('database_name')
            ->pluck('id');

        $removed = 0;
        $expired = BackupRun::query()->usable()
            ->whereNotIn('id', $newestIds)
            ->where(fn($query) => $query
                ->where(fn($daily) => $daily->where('is_monthly', false)->where('finished_at', '<', $dailyCutoff))
                ->orWhere(fn($monthly) => $monthly->where('is_monthly', true)->where('finished_at', '<', $monthlyCutoff)))
            ->get();

        foreach ($expired as $run) {
            if ($run->path !== null) {
                $this->disk()->delete($run->path);
            }
            $run->update(['pruned_at' => now()]);
            $removed++;
        }

        return $removed;
    }

    /**
     * Problems that mean a database is not safely backed up right now.
     *
     * @return list<string>
     */
    public function problems(): array
    {
        $maxAge = now()->subHours($this->intConfig('backup.max_age_hours'));
        $problems = [];

        foreach ($this->databases() as $database) {
            $latest = BackupRun::query()->usable()
                ->where('database_name', $database['name'])
                ->latest('finished_at')
                ->first();

            if ($latest === null) {
                $problems[] = "{$database['name']}: no backup exists.";

                continue;
            }

            if ($latest->finished_at === null || $latest->finished_at->lt($maxAge)) {
                $problems[] = "{$database['name']}: newest backup is older than " . $this->intConfig('backup.max_age_hours') . ' hours.';

                continue;
            }

            if ($latest->path === null || ! $this->disk()->exists($latest->path) || $this->disk()->size($latest->path) !== $latest->size_bytes) {
                $problems[] = "{$database['name']}: the newest backup file is missing or its size changed.";
            }
        }

        return $problems;
    }

    public function disk(): \Illuminate\Contracts\Filesystem\Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function intConfig(string $key): int
    {
        $value = config($key);

        return is_numeric($value) ? (int) $value : 0;
    }

    public function workDir(): string
    {
        $dir = storage_path('app/backup-tmp');
        if (! is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        return $dir;
    }

    private function dump(string $databaseName, string $outputPath): void
    {
        $connection = $this->centralConnection();

        $result = Process::timeout($this->intConfig('backup.dump_timeout_seconds'))
            ->env(['PGPASSWORD' => $this->stringOption($connection['password'] ?? null)])
            ->run([
                $this->binary('pg_dump_binary'),
                '--format=custom',
                '--no-owner',
                '--no-privileges',
                '--host', $this->stringOption($connection['host'] ?? null, '127.0.0.1'),
                '--port', $this->stringOption($connection['port'] ?? null, '5432'),
                '--username', $this->stringOption($connection['username'] ?? null),
                '--file', $outputPath,
                $databaseName,
            ]);

        if ($result->failed()) {
            throw new RuntimeException('pg_dump failed: ' . trim($result->errorOutput()));
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function centralConnection(): array
    {
        $name = config('database.default');
        /** @var array<string, mixed> $connection */
        $connection = config('database.connections.' . (is_string($name) ? $name : 'pgsql'), []);

        return $connection;
    }

    private function centralDatabaseName(): string
    {
        return $this->stringOption($this->centralConnection()['database'] ?? null);
    }

    private function diskName(): string
    {
        return $this->stringOption(config('backup.disk'), 'backups');
    }

    private function publicKey(): string
    {
        return $this->stringOption(config('backup.public_key'));
    }

    private function binary(string $key): string
    {
        return $this->stringOption(config('backup.' . $key), str_replace('_binary', '', $key));
    }

    private function stringOption(mixed $value, string $default = ''): string
    {
        return is_string($value) && $value !== '' ? $value : (is_scalar($value) && (string) $value !== '' ? (string) $value : $default);
    }
}
