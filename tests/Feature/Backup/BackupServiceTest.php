<?php

declare(strict_types=1);

uses(Tests\LandlordTestCase::class);
use App\Enums\BackupStatus;
use App\Models\Landlord\BackupRun;
use App\Services\Backup\BackupEncryptor;
use App\Services\Backup\BackupService;
use App\Services\Backup\BackupVerifier;
use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('backups');
    $this->keys = (new BackupEncryptor())->generateKeyPair();
    config([
        'backup.disk' => 'backups',
        'backup.public_key' => $this->keys['public'],
        'filesystems.disks.backups.bucket' => 'test-bucket',
        'backup.allow_client_version_mismatch' => true,
    ]);
    $this->service = app(BackupService::class);
});

function fakePgDump(string $contents = 'PGDMP-fake-dump'): void
{
    Process::fake(function (PendingProcess $process) use ($contents): ProcessResult {
        $command = $process->command;
        $file = is_array($command) ? array_search('--file', $command, true) : false;
        if (is_array($command) && $file !== false) {
            file_put_contents($command[$file + 1], $contents);
        }

        return Process::result('pg_dump (PostgreSQL) 16.0');
    });
}

test('a backup is dumped, encrypted, uploaded and recorded', function () {
    fakePgDump('PGDMP-tenant-data');

    $run = $this->service->backup('tenant_abc_2026_01_01', 'abc');

    expect($run->status)->toBe(BackupStatus::Completed)
        ->and($run->tenant_id)->toBe('abc')
        ->and($run->size_bytes)->toBeGreaterThan(0)
        ->and($run->is_monthly)->toBeTrue();
    Storage::disk('backups')->assertExists($run->path);

    $stored = Storage::disk('backups')->get($run->path);
    expect(hash('sha256', $stored))->toBe($run->checksum)
        ->and($stored)->not->toContain('PGDMP-tenant-data');

    $encrypted = tempnam(sys_get_temp_dir(), 'e');
    $plain = tempnam(sys_get_temp_dir(), 'p');
    file_put_contents($encrypted, $stored);
    (new BackupEncryptor())->decryptFile($encrypted, $plain, $this->keys['private']);
    expect(file_get_contents($plain))->toBe('PGDMP-tenant-data');
    unlink($encrypted);
    unlink($plain);
});
test('the dump password is passed through the environment, not the command line', function () {
    fakePgDump();
    config(['database.connections.' . config('database.default') . '.password' => 's3cret-pw']);

    $this->service->backup('tenant_abc_2026_01_01', 'abc');

    Process::assertRan(fn(PendingProcess $process) => ($process->environment['PGPASSWORD'] ?? null) === 's3cret-pw'
        && ! in_array('s3cret-pw', (array) $process->command, true));
});
test('temporary plaintext files never remain after a backup', function () {
    fakePgDump();

    $this->service->backup('tenant_abc_2026_01_01', 'abc');

    expect(glob($this->service->workDir() . '/tenant_abc_2026_01_01*'))->toBeEmpty();
});
test('a failing pg_dump is recorded as failed without uploading', function () {
    Process::fake(fn() => Process::result(output: '', errorOutput: 'connection refused', exitCode: 1));

    $run = $this->service->backup('tenant_abc_2026_01_01', 'abc');

    expect($run->status)->toBe(BackupStatus::Failed)
        ->and($run->error)->toContain('connection refused')
        ->and($run->path)->toBeNull();
    expect(Storage::disk('backups')->allFiles())->toBeEmpty();
});
test('only the first completed backup of a month is marked monthly', function () {
    fakePgDump();

    $first = $this->service->backup('tenant_abc_2026_01_01', 'abc');
    $second = $this->service->backup('tenant_abc_2026_01_01', 'abc');

    expect($first->is_monthly)->toBeTrue()->and($second->is_monthly)->toBeFalse();
});
test('preflight fails clearly when the public key is missing', function () {
    config(['backup.public_key' => null]);

    expect(fn() => $this->service->preflight())->toThrow(RuntimeException::class, 'BACKUP_ENCRYPTION_PUBLIC_KEY');
});
test('preflight fails clearly when the s3 backup disk has no bucket', function () {
    config(['filesystems.disks.backups.bucket' => null]);

    expect(fn() => $this->service->preflight())->toThrow(RuntimeException::class, 'BACKUP_DISK_BUCKET');
});
test('preflight fails clearly when pg_dump is not installed', function () {
    Process::fake(fn() => Process::result(output: '', errorOutput: 'not found', exitCode: 127));

    expect(fn() => $this->service->preflight())->toThrow(RuntimeException::class, 'pg_dump is not installed');
});
test('preflight refuses a pg_dump whose version differs from the database server', function () {
    config(['backup.allow_client_version_mismatch' => false]);
    Process::fake(fn() => Process::result('pg_dump (PostgreSQL) 99.0'));

    expect(fn() => $this->service->preflight())->toThrow(RuntimeException::class, 'Install postgresql-client-');
});
test('preflight accepts a pg_dump that matches the database server version', function () {
    config(['backup.allow_client_version_mismatch' => false]);
    $serverMajor = intdiv((int) DB::selectOne('show server_version_num')->server_version_num, 10000);
    Process::fake(fn() => Process::result("pg_dump (PostgreSQL) {$serverMajor}.4"));

    expect(fn() => $this->service->preflight())->not->toThrow(Throwable::class);
});
test('preflight passes with a valid key, bucket and pg_dump', function () {
    fakePgDump();

    expect(fn() => $this->service->preflight())->not->toThrow(Throwable::class);
});
test('prune removes expired daily backups but keeps the newest and monthly ones', function () {
    Storage::disk('backups')->put('old-daily', 'x');
    Storage::disk('backups')->put('old-monthly', 'x');
    Storage::disk('backups')->put('ancient-monthly', 'x');
    Storage::disk('backups')->put('newest', 'x');

    $oldDaily = BackupRun::factory()->create(['database_name' => 'db_a', 'path' => 'old-daily', 'finished_at' => now()->subDays(45)]);
    $oldMonthly = BackupRun::factory()->create(['database_name' => 'db_a', 'path' => 'old-monthly', 'is_monthly' => true, 'finished_at' => now()->subMonths(6)]);
    $ancientMonthly = BackupRun::factory()->create(['database_name' => 'db_a', 'path' => 'ancient-monthly', 'is_monthly' => true, 'finished_at' => now()->subMonths(14)]);
    $newest = BackupRun::factory()->create(['database_name' => 'db_a', 'path' => 'newest', 'finished_at' => now()->subHour()]);

    expect($this->service->prune())->toBe(2);

    expect($oldDaily->refresh()->pruned_at)->not->toBeNull()
        ->and($ancientMonthly->refresh()->pruned_at)->not->toBeNull()
        ->and($oldMonthly->refresh()->pruned_at)->toBeNull()
        ->and($newest->refresh()->pruned_at)->toBeNull();
    Storage::disk('backups')->assertMissing('old-daily');
    Storage::disk('backups')->assertExists('old-monthly');
    Storage::disk('backups')->assertExists('newest');
});
test('prune never deletes the only backup of a database even when it is old', function () {
    Storage::disk('backups')->put('lonely', 'x');
    $lonely = BackupRun::factory()->create(['database_name' => 'db_b', 'path' => 'lonely', 'finished_at' => now()->subDays(200)]);

    expect($this->service->prune())->toBe(0);

    expect($lonely->refresh()->pruned_at)->toBeNull();
    Storage::disk('backups')->assertExists('lonely');
});
test('problems reports a database with no backup, a stale one, and a missing file', function () {
    $tenantDb = $this->tenant->database()->getName();

    $problems = $this->service->problems();
    expect(collect($problems)->contains(fn($p) => str_starts_with($p, "{$tenantDb}: no backup")))->toBeTrue();

    Storage::disk('backups')->put('stale', 'abc');
    BackupRun::factory()->create(['database_name' => $tenantDb, 'path' => 'stale', 'size_bytes' => 3, 'finished_at' => now()->subHours(30)]);
    expect(collect($this->service->problems())->contains(fn($p) => str_starts_with($p, "{$tenantDb}: newest backup is older")))->toBeTrue();

    BackupRun::factory()->create(['database_name' => $tenantDb, 'path' => 'gone', 'size_bytes' => 3, 'finished_at' => now()->subMinutes(5)]);
    expect(collect($this->service->problems())->contains(fn($p) => str_starts_with($p, "{$tenantDb}: the newest backup file is missing")))->toBeTrue();

    Storage::disk('backups')->put('fresh', 'abc');
    BackupRun::factory()->create(['database_name' => $tenantDb, 'path' => 'fresh', 'size_bytes' => 3, 'finished_at' => now()]);
    expect(collect($this->service->problems())->contains(fn($p) => str_starts_with($p, "{$tenantDb}:")))->toBeFalse();
});
test('verification rejects a backup whose stored file no longer matches its checksum', function () {
    Storage::disk('backups')->put('swapped', 'attacker-controlled bytes');
    $run = BackupRun::factory()->create(['database_name' => 'db_c', 'path' => 'swapped', 'checksum' => hash('sha256', 'what was uploaded')]);

    expect(fn() => app(BackupVerifier::class)->verify($run, $this->keys['private']))
        ->toThrow(RuntimeException::class, 'Checksum mismatch');

    expect($run->refresh()->verify_status)->toBe('failed')
        ->and($run->verify_error)->toContain('Checksum mismatch');
});
test('backup:run reports a readable error and fails when preflight fails', function () {
    config(['backup.public_key' => null]);

    $this->artisan('backup:run')->expectsOutputToContain('BACKUP_ENCRYPTION_PUBLIC_KEY')->assertFailed();
});
test('backup:check fails when a database has no fresh backup and passes otherwise', function () {
    $this->artisan('backup:check')->assertFailed();
});
test('backup:generate-key prints a usable key pair', function () {
    $this->artisan('backup:generate-key')->expectsOutputToContain('BACKUP_ENCRYPTION_PUBLIC_KEY=')->assertSuccessful();
});
test('backup:verify demands a private key file', function () {
    $this->artisan('backup:verify')->expectsOutputToContain('--private-key-file')->assertFailed();
});
test('backup:decrypt turns a stored backup back into the original dump', function () {
    fakePgDump('PGDMP-restore-me');
    $run = $this->service->backup('tenant_abc_2026_01_01', 'abc');

    $dir = sys_get_temp_dir() . '/dec-' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents("{$dir}/in.enc", Storage::disk('backups')->get($run->path));
    file_put_contents("{$dir}/key", $this->keys['private']);

    $this->artisan('backup:decrypt', ['input' => "{$dir}/in.enc", 'output' => "{$dir}/out.dump", '--private-key-file' => "{$dir}/key"])->assertSuccessful();

    expect(file_get_contents("{$dir}/out.dump"))->toBe('PGDMP-restore-me');
    array_map('unlink', glob("{$dir}/*"));
    rmdir($dir);
});
test('backup:decrypt refuses to overwrite and leaves no partial file on a wrong key', function () {
    fakePgDump();
    $run = $this->service->backup('tenant_abc_2026_01_01', 'abc');
    $dir = sys_get_temp_dir() . '/dec-' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents("{$dir}/in.enc", Storage::disk('backups')->get($run->path));
    file_put_contents("{$dir}/key", (new BackupEncryptor())->generateKeyPair()['private']);

    $this->artisan('backup:decrypt', ['input' => "{$dir}/in.enc", 'output' => "{$dir}/out.dump", '--private-key-file' => "{$dir}/key"])->assertFailed();

    expect(file_exists("{$dir}/out.dump"))->toBeFalse();
    array_map('unlink', glob("{$dir}/*"));
    rmdir($dir);
});
