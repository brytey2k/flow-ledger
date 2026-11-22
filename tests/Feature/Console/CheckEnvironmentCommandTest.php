<?php

declare(strict_types=1);

use App\Support\Deployment\ConfigurationAudit;
use App\Support\Deployment\ConfigurationIssue;
use App\Support\Deployment\DatabaseRole;
use Tests\TestCase;

uses(TestCase::class);

/* A configuration that passes every production check. */
beforeEach(function () {
    // The local and CI database roles are superusers; checks against a real role are in DatabaseRoleTest.
    $this->mock(DatabaseRole::class)->allows('privileges')->andReturn(['name' => 'flow_ledger', 'superuser' => false, 'createdb' => true])->byDefault();

    config([
        'app.env' => 'production',
        'app.debug' => false,
        'app.url' => 'https://flowledger.example.com',
        'app.central_url' => 'flowledger.example.com',
        'session.secure' => true,
        'session.encrypt' => true,
        'mail.default' => 'mailgun',
        'mail.from.address' => 'no-reply@mg.example.com',
        'queue.default' => 'redis',
        'backup.public_key' => 'age1examplepublickey',
        'backup.disk' => 'backups',
        'filesystems.disks.backups.key' => 'key',
        'filesystems.disks.backups.secret' => 'secret',
        'filesystems.disks.backups.bucket' => 'flow-ledger-backups',
        'filesystems.disks.backups.root' => 'flow-ledger',
        'sso.client_id' => 'flow-ledger',
        'sso.client_secret' => 'secret',
        'sso.idp_url' => 'https://iam.example.com',
        'sso.redirect_uri' => 'https://flowledger.example.com/auth/sso/callback',
        'sso.jwks_uri' => 'https://iam.example.com/.well-known/jwks.json',
        'sso.verify_ssl' => true,
        'filesystems.disks.local.driver' => 's3',
        'logging.default' => 'stack',
        'logging.channels.stack.driver' => 'stack',
        'logging.channels.stack.channels' => ['daily'],
        'logging.channels.daily.level' => 'warning',
        'sentry.dsn' => 'https://key@sentry.example.com/1',
    ]);
});

/** @return list<string> */
function auditSettings(bool $production, bool $blockingOnly = false): array
{
    $issues = app(ConfigurationAudit::class)->issues($production);

    return array_values(array_map(
        fn(ConfigurationIssue $issue): string => $issue->setting,
        array_filter($issues, fn(ConfigurationIssue $issue): bool => ! $blockingOnly || $issue->blocking),
    ));
}

test('a production-ready configuration passes', function () {
    expect(auditSettings(production: true))->toBe([]);

    $this->artisan('app:check-environment --production')->assertSuccessful();
});

test('a development env copied to production fails the production check', function () {
    config([
        'app.env' => 'local',
        'app.debug' => true,
        'app.url' => 'http://flow-ledger.test',
        'app.central_url' => 'flow-ledger.test',
        'session.secure' => false,
        'session.encrypt' => false,
        'mail.default' => 'log',
        'mail.from.address' => 'hello@example.com',
        'queue.default' => 'sync',
        'backup.public_key' => null,
        'filesystems.disks.backups.bucket' => null,
    ]);

    expect(auditSettings(production: true, blockingOnly: true))->toBe([
        'APP_ENV', 'APP_DEBUG', 'APP_URL', 'CENTRAL_APP_URL', 'SESSION_SECURE_COOKIE', 'SESSION_ENCRYPT', 'MAIL_MAILER',
        'MAIL_FROM_ADDRESS', 'QUEUE_CONNECTION', 'BACKUP_ENCRYPTION_PUBLIC_KEY', 'BACKUP_DISK_BUCKET',
    ]);

    $this->artisan('app:check-environment --production')
        ->expectsOutputToContain('APP_DEBUG')
        ->expectsOutputToContain('blocking issue(s)')
        ->assertFailed();
});

test('outside production the same issues are reported but do not fail the check', function () {
    config(['app.env' => 'staging', 'app.debug' => true, 'mail.default' => 'log']);

    expect(auditSettings(production: false))->toContain('APP_DEBUG', 'MAIL_MAILER')
        ->and(auditSettings(production: false, blockingOnly: true))->toBe([]);

    $this->artisan('app:check-environment')->assertSuccessful();
});

test('a missing app key fails even outside production', function () {
    config(['app.key' => '']);

    expect(auditSettings(production: false, blockingOnly: true))->toBe(['APP_KEY']);
    $this->artisan('app:check-environment')->assertFailed();
});

test('the central domain must be the real domain APP_URL points at', function () {
    config(['app.central_url' => 'other.example.com']);
    expect(auditSettings(production: true, blockingOnly: true))->toBe(['CENTRAL_APP_URL / APP_URL']);

    config(['app.central_url' => 'localhost']);
    expect(auditSettings(production: true, blockingOnly: true))->toBe(['CENTRAL_APP_URL']);
});

test('backups need their encryption key and an offsite disk with credentials', function () {
    config(['filesystems.disks.backups.key' => null, 'filesystems.disks.backups.secret' => '']);
    expect(auditSettings(production: true, blockingOnly: true))->toBe(['BACKUP_DISK_KEY / BACKUP_DISK_SECRET']);

    config(['filesystems.disks.backups.key' => 'key', 'filesystems.disks.backups.secret' => 'secret', 'filesystems.disks.backups.root' => '']);
    expect(auditSettings(production: true, blockingOnly: true))->toBe(['BACKUP_DISK_ROOT'])
        ->and(auditSettings(production: false))->toBe(['BACKUP_DISK_ROOT'])
        ->and(auditSettings(production: false, blockingOnly: true))->toBe([]);

    config(['backup.disk' => 'public']);
    expect(auditSettings(production: true, blockingOnly: true))->toBe(['BACKUP_DISK']);

    config(['backup.disk' => 'nowhere']);
    expect(auditSettings(production: true, blockingOnly: true))->toBe(['BACKUP_DISK']);
});

test('a half-configured SSO client blocks a production deploy, an unconfigured one only warns', function () {
    config(['sso.client_secret' => null, 'sso.jwks_uri' => 'http://iam.example.com/.well-known/jwks.json', 'sso.verify_ssl' => false]);
    expect(auditSettings(production: true, blockingOnly: true))->toBe(['SSO_CLIENT_SECRET', 'SSO_JWKS_URI', 'SSO_VERIFY_SSL']);

    config(['sso.client_id' => null]);
    expect(auditSettings(production: true))->toBe(['SSO_CLIENT_ID'])
        ->and(auditSettings(production: true, blockingOnly: true))->toBe([]);
});

test('attachments on this server only warn, but a public or shared disk blocks everywhere', function () {
    config(['filesystems.disks.local.driver' => 'local']);
    expect(auditSettings(production: true))->toBe(['filesystems.disks.local'])
        ->and(auditSettings(production: true, blockingOnly: true))->toBe([]);

    config(['filesystems.disks.local.visibility' => 'public']);
    expect(auditSettings(production: false, blockingOnly: true))->toBe(['filesystems.disks.local']);

    config(['filesystems.disks.local.visibility' => 'private', 'tenancy.filesystem.disks' => ['public']]);
    expect(auditSettings(production: false, blockingOnly: true))->toBe(['tenancy.filesystem.disks']);
});

test('noisy logging and missing error tracking are warnings, never blocking', function () {
    config([
        'logging.channels.stack.channels' => ['single'],
        'logging.channels.single.level' => 'debug',
        'sentry.dsn' => null,
    ]);

    expect(auditSettings(production: true))->toBe(['LOG_LEVEL', 'LOG_STACK', 'SENTRY_LARAVEL_DSN'])
        ->and(auditSettings(production: true, blockingOnly: true))->toBe([]);
});

test('the database role must be able to create organisation databases, and should not be a superuser', function (array|null $role, array $blocking, array $all) {
    $this->mock(DatabaseRole::class)->allows('privileges')->andReturn($role);

    expect(auditSettings(production: true, blockingOnly: true))->toBe($blocking)
        ->and(auditSettings(production: true))->toBe($all);
})->with([
    'no CREATEDB' => [['name' => 'flow_ledger', 'superuser' => false, 'createdb' => false], ['DB_USERNAME'], ['DB_USERNAME']],
    'superuser' => [['name' => 'postgres', 'superuser' => true, 'createdb' => true], [], ['DB_USERNAME']],
    'unreachable' => [null, ['DB_HOST / DB_USERNAME / DB_PASSWORD'], ['DB_HOST / DB_USERNAME / DB_PASSWORD']],
]);

test('localhost is a central domain only in local development and tests', function () {
    $domains = fn(string $environment): array => (function () use ($environment): array {
        $previous = getenv('APP_ENV');
        putenv("APP_ENV={$environment}");
        $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $environment;

        try {
            return (require config_path('tenancy.php'))['central_domains'];
        } finally {
            putenv($previous === false ? 'APP_ENV' : "APP_ENV={$previous}");
            $_ENV['APP_ENV'] = $_SERVER['APP_ENV'] = $previous === false ? null : $previous;
        }
    })();

    expect($domains('production'))->not->toContain('localhost')
        ->and($domains('testing'))->toContain('localhost');
});
